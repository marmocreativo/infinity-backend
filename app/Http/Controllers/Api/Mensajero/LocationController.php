<?php

namespace App\Http\Controllers\Api\Mensajero;

use App\Events\CourierLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Models\CourierLocation;
use App\Models\CourierShift;

class LocationController extends Controller
{
    public function store(StoreLocationRequest $request)
    {
        $shift = CourierShift::where('courier_id', $request->user()->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        abort_unless($shift, 422, 'No puedes reportar ubicación sin un turno activo.');

        $location = CourierLocation::create([
            'courier_id' => $request->user()->id,
            'shift_id' => $shift->id,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'recorded_at' => $request->recorded_at ?? now(),
        ]);

        broadcast(new CourierLocationUpdated($location))->toOthers();

        return response()->json(['location' => $location], 201);
    }
}