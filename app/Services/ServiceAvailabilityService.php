<?php

namespace App\Services;

use App\Models\CourierShift;

class ServiceAvailabilityService
{
    public function status(): array
    {
        $tz = config('service_rules.timezone');
        $cutoffTime = config('service_rules.order_cutoff_time');

        $now = now($tz);
        $cutoff = $now->copy()->setTimeFromTimeString($cutoffTime);

        $afterCutoff = $now->greaterThanOrEqualTo($cutoff);

        $couriersOnDuty = CourierShift::whereNull('ended_at')
            ->whereHas('courier', fn ($q) => $q->where('status', 'active'))
            ->distinct()
            ->count('courier_id');

        $reason = null;
        $message = null;
        $scheduledFor = null;

        if ($afterCutoff) {
            // Pasada la hora límite el pedido no se bloquea: se programa para el día siguiente
            $scheduledFor = $now->copy()->addDay()->toDateString();
            $reason = 'after_cutoff';
            $message = 'Ya pasó la hora límite de las ' . $this->formatTime($cutoffTime)
                . '. Tu pedido se programará para mañana y se asignará a primera hora.';
        } elseif ($couriersOnDuty === 0) {
            $reason = 'no_couriers';
            $message = 'Por el momento no hay repartidores en turno. Intenta de nuevo en unos minutos.';
        }

        return [
            'can_order' => $reason !== 'no_couriers',
            'reason' => $reason,
            'message' => $message,
            'scheduled_for' => $scheduledFor,
            'cutoff_time' => $cutoffTime,
            'couriers_on_duty' => $couriersOnDuty,
            'per_km' => (float) config('pricing.per_km'),
            'urgent_surcharge' => (float) config('service_rules.urgent_surcharge'),
            'urgent_max_hours' => (int) config('service_rules.urgent_max_hours'),
            'cancellation_fee_percent' => (float) config('service_rules.cancellation_fee_percent'),
            'return_surcharge_percent' => (float) config('service_rules.return_surcharge_percent'),
        ];
    }

    private function formatTime(string $hhmm): string
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        $suffix = $h >= 12 ? 'p. m.' : 'a. m.';
        $h12 = $h % 12 ?: 12;

        return sprintf('%d:%02d %s', $h12, $m, $suffix);
    }
}
