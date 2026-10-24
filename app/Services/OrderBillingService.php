<?php

namespace App\Services;

use App\Models\Order;

class OrderBillingService
{
    public function routePrice(float $distanceKm): float
    {
        $price = (float) config('pricing.base_fee') + (float) config('pricing.per_km') * $distanceKm;

        return round(max((float) config('pricing.minimum'), $price), 2);
    }

    /** Desglose de precio de un pedido nuevo */
    public function breakdown(float $distanceKm, bool $urgent): array
    {
        $routePrice = $this->routePrice($distanceKm);
        $surcharge = $urgent ? (float) config('service_rules.urgent_surcharge') : 0.0;

        return [
            'route_price' => $routePrice,
            'urgent_surcharge' => $surcharge,
            'total' => round($routePrice + $surcharge, 2),
        ];
    }

    /** Cargo que se aplicaría hoy si el cliente cancela (0 si aún no está asignado) */
    public function cancellationFee(Order $order): float
    {
        if ($order->status === 'pending') {
            return 0.0;
        }

        return round((float) $order->quoted_price * (float) config('service_rules.cancellation_fee_percent') / 100, 2);
    }

    /** Debe llamarse ANTES de cambiar el status a "cancelled" */
    public function cancel(Order $order): void
    {
        $fee = $this->cancellationFee($order);

        $this->settle($order, $fee, ['cancellation_fee' => $fee]);
    }

    /** Retorno a remitente: precio cotizado + recargo */
    public function markReturned(Order $order): void
    {
        $quoted = (float) $order->quoted_price;
        $surcharge = round($quoted * (float) config('service_rules.return_surcharge_percent') / 100, 2);

        $this->settle($order, round($quoted + $surcharge, 2), ['return_surcharge' => $surcharge]);
    }

    /**
     * Fija el nuevo monto a cobrar, calcula reembolso si ya se pagó de más
     * y deja el saldo pendiente si se pagó de menos.
     */
    private function settle(Order $order, float $newPrice, array $extra): void
    {
        $paid = (float) $order->amount_paid;
        $refund = 0.0;

        if ($paid > $newPrice) {
            $refund = round($paid - $newPrice, 2);
            $paid = $newPrice;
        }

        $order->forceFill($extra);
        $order->price = $newPrice;
        $order->amount_paid = $paid;
        $order->refunded_amount = round((float) $order->refunded_amount + $refund, 2);
        $order->payment_status = ($paid + 0.005 >= $newPrice) ? 'paid' : 'pending';
        $order->save();

        $payment = $order->payment;

        if ($payment) {
            $status = match (true) {
                $newPrice <= 0 && $refund > 0 => 'refunded',
                $newPrice <= 0 => 'waived',
                $order->payment_status === 'paid' => 'paid',
                default => 'pending',
            };

            $payment->update(['amount' => $newPrice, 'status' => $status]);
        }
    }
}