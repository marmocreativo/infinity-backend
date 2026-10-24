<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierShift;
use App\Models\User;

class CourierController extends Controller
{
    public function onDuty()
    {
        $couriers = User::role('mensajero')
            ->whereHas('courierProfile', fn ($q) => $q->where('is_on_duty', true))
            ->with(['courierProfile'])
            ->get()
            ->map(function ($courier) {
                $shift = $courier->shifts()->whereNull('ended_at')->latest('started_at')->first();
                $lastLocation = $shift?->locations()->latest('recorded_at')->first();

                return [
                    'id' => $courier->id,
                    'name' => $courier->name,
                    'photo_url' => $courier->photo_url,
                    'shift_id' => $shift?->id,
                    'started_at' => $shift?->started_at,
                    'last_location' => $lastLocation ? [
                        'lat' => $lastLocation->lat,
                        'lng' => $lastLocation->lng,
                        'recorded_at' => $lastLocation->recorded_at,
                    ] : null,
                ];
            });

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