<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Crédito Infinity: los pedidos con método "credit" no se cobran al momento.
 * Cada pedido tiene un plazo máximo para pagarse (7 o 15 días desde que se creó,
 * según lo defina el administrador para la clínica). La clínica puede pagar uno o
 * varios pedidos cuando quiera. Si algún pedido pasa su plazo sin pagarse, no se
 * pueden crear pedidos nuevos hasta que se cubra.
 */
class CreditService
{
    public const CYCLES = [7, 15];

    public function tz(): string
    {
        return config('service_rules.timezone');
    }

    public function cycleDays(Clinic $clinic): int
    {
        return in_array((int) $clinic->credit_cycle_days, self::CYCLES, true) ? (int) $clinic->credit_cycle_days : 15;
    }

    /** Lo que se le cobra al cliente por un pedido a crédito */
    public function owed(Order $order): float
    {
        if ($order->status === 'cancelled') {
            return round((float) $order->cancellation_fee, 2);
        }

        return round((float) $order->price, 2);
    }

    public function dueAt(Order $order, int $days): Carbon
    {
        return Carbon::parse($order->created_at)->setTimezone($this->tz())->addDays($days)->endOfDay();
    }

    public function summary(Clinic $clinic): array
    {
        $days = $this->cycleDays($clinic);
        $now = now($this->tz());

        $items = $clinic->orders()
            ->where('payment_method', 'credit')
            ->with('payment')
            ->latest()
            ->get()
            ->map(function (Order $o) use ($days, $now) {
                $amount = $this->owed($o);
                $due = $this->dueAt($o, $days);
                $paid = $o->payment_status === 'paid';

                $status = match (true) {
                    $paid => 'paid',
                    $due->lt($now) => 'overdue',
                    default => 'open',
                };

                $reference = ! $paid && $o->payment?->gateway === 'oxxo' && $o->payment?->status === 'pending'
                    ? $o->payment->gateway_reference
                    : null;

                return [
                    'id' => $o->id,
                    'order_status' => $o->status,
                    'amount' => $amount,
                    'created_at' => $o->created_at?->toIso8601String(),
                    'due_at' => $due->toIso8601String(),
                    'days_left' => $paid ? null : (int) $now->copy()->startOfDay()->diffInDays($due->copy()->startOfDay(), false),
                    'status' => $status, // open | overdue | paid
                    'oxxo_reference' => $reference,
                ];
            })
            ->filter(fn (array $i) => $i['amount'] > 0)
            ->values();

        $unpaid = $items->where('status', '!=', 'paid');
        $overdue = $items->where('status', 'overdue');

        return [
            'credit_enabled' => (bool) $clinic->credit_enabled,
            'cycle_days' => $days,
            'balance' => round($unpaid->sum('amount'), 2),
            'debt' => round($overdue->sum('amount'), 2), // vencido
            'overdue_count' => $overdue->count(),
            'blocked' => $overdue->isNotEmpty(),
            'next_due_at' => $unpaid->where('status', 'open')->min('due_at'),
            'orders' => $items->take(300)->values(),
        ];
    }

    public function debt(Clinic $clinic): float
    {
        return (float) $this->summary($clinic)['debt'];
    }

    /** Pedidos a crédito de la clínica, sin pagar, validados contra la lista pedida */
    private function unpaidOrders(Clinic $clinic, array $ids): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        $orders = $clinic->orders()
            ->where('payment_method', 'credit')
            ->where('payment_status', '!=', 'paid')
            ->whereIn('id', $ids)
            ->with('payment')
            ->get();

        abort_if(
            $orders->count() !== count($ids) || $orders->isEmpty(),
            422,
            'Alguno de los pedidos no existe, no es a crédito o ya está pagado.'
        );

        return $orders;
    }

    private function settle(Collection $orders, string $gateway, string $reference): float
    {
        $total = 0.0;

        foreach ($orders as $o) {
            $amount = $this->owed($o);
            $total += $amount;

            $o->forceFill(['amount_paid' => $amount, 'payment_status' => 'paid'])->save();
            $o->payment?->update([
                'amount' => $amount,
                'status' => 'paid',
                'gateway' => $gateway,
                'gateway_reference' => $reference,
                'paid_at' => now(),
            ]);
        }

        return round($total, 2);
    }

    /**
     * La clínica paga uno o varios pedidos.
     *  - card: cobro inmediato (SIMULADO hasta integrar la pasarela real)
     *  - oxxo: genera una referencia; los pedidos quedan pendientes hasta que se confirme el depósito
     */
    public function payOrders(Clinic $clinic, array $ids, string $method): array
    {
        $orders = $this->unpaidOrders($clinic, $ids);
        $total = round($orders->sum(fn (Order $o) => $this->owed($o)), 2);

        if ($method === 'card') {
            $this->settle($orders, 'card', 'sim_' . Str::random(20));

            return ['method' => 'card', 'total' => $total, 'paid' => true];
        }

        $reference = (string) random_int(10000000, 99999999) . (string) random_int(100000, 999999);

        foreach ($orders as $o) {
            $o->payment?->update(['gateway' => 'oxxo', 'gateway_reference' => $reference, 'status' => 'pending']);
        }

        return ['method' => 'oxxo', 'total' => $total, 'paid' => false, 'reference' => $reference];
    }

    /** El admin confirma que se pagaron estos pedidos (depósito OXXO, transferencia, etc.) */
    public function registerPayment(Clinic $clinic, array $ids): float
    {
        $orders = $this->unpaidOrders($clinic, $ids);

        return $this->settle($orders, 'manual', 'admin_' . Str::random(10));
    }
}
