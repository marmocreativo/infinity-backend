<?php

namespace App\Services;

use App\Models\AssignmentSetting;
use App\Models\Order;
use App\Models\User;

class OrderAssignmentService
{
    public function tryAutoAssign(Order $order): bool
    {
        $setting = AssignmentSetting::first();

        if (! $setting || $setting->mode !== 'automatic') {
            return false;
        }

        $courier = $this->findClosestAvailableCourier($order->pickup_lat, $order->pickup_lng);

        if (! $courier) {
            return false;
        }

        $order->update([
            'courier_id' => $courier->id,
            'status' => 'assigned',
            'assignment_type' => 'automatic',
        ]);

        return true;
    }

    private function findClosestAvailableCourier(float $pickupLat, float $pickupLng): ?User
    {
        $couriers = User::role('mensajero')
            ->whereHas('courierProfile', fn ($q) => $q->where('is_on_duty', true))
            ->with(['shifts' => fn ($q) => $q->whereNull('ended_at')->latest('started_at')->limit(1)])
            ->get();

        $closest = null;
        $closestDistance = null;

        foreach ($couriers as $courier) {
            $shift = $courier->shifts->first();

            if (! $shift) {
                continue;
            }

            $lastLocation = $shift->locations()->latest('recorded_at')->first();

            if (! $lastLocation) {
                continue;
            }

            $distance = $this->haversineDistance(
                $pickupLat,
                $pickupLng,
                (float) $lastLocation->lat,
                (float) $lastLocation->lng
            );

            if ($closestDistance === null || $distance < $closestDistance) {
                $closestDistance = $distance;
                $closest = $courier;
            }
        }

        return $closest;
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}