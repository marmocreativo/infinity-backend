<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierLocation;
use App\Models\CourierShift;
use App\Models\User;

class CourierController extends Controller
{
    /**
     * Mensajeros para el mapa del admin: los que están en turno y también los activos
     * fuera de turno (con su última ubicación conocida), marcados con `on_duty`.
     */
    public function onDuty()
    {
        $couriers = User::role('mensajero')
            ->where(function ($q) {
                $q->where('status', 'active')
                    ->orWhereHas('courierProfile', fn ($p) => $p->where('is_on_duty', true));
            })
            ->with(['courierProfile'])
            ->get()
            ->map(function ($courier) {
                $onDuty = (bool) $courier->courierProfile?->is_on_duty;
                $shift = $onDuty
                    ? $courier->shifts()->whereNull('ended_at')->latest('started_at')->first()
                    : null;

                $lastLocation = $shift?->locations()->latest('recorded_at')->first()
                    ?? CourierLocation::where('courier_id', $courier->id)->latest('recorded_at')->first();

                return [
                    'id' => $courier->id,
                    'name' => $courier->name,
                    'photo_url' => $courier->photo_url,
                    'on_duty' => $onDuty,
                    'shift_id' => $shift?->id,
                    'started_at' => $shift?->started_at,
                    'last_location' => $lastLocation ? [
                        'lat' => $lastLocation->lat,
                        'lng' => $lastLocation->lng,
                        'recorded_at' => $lastLocation->recorded_at,
                    ] : null,
                ];
            })
            ->values();

        return response()->json(['data' => $couriers]);
    }

    public function shifts(User $courier)
    {
        $shifts = $courier->shifts()->latest('started_at')->paginate(20);

        return response()->json($shifts);
    }

    public function shiftLocations(CourierShift $shift)
    {
        $locations = $shift->locations()->orderBy('recorded_at')->get();

        return response()->json(['data' => $locations]);
    }
}
