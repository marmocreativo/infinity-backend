<?php

namespace App\Http\Controllers\Api\Mensajero;

use App\Events\CourierLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Models\CourierLocation;
use App\Models\CourierShift;
use App\Services\OrderDispatchService;
use App\Support\Impersonation;

class LocationController extends Controller
{
    public function store(StoreLocationRequest $request)
    {
        // Un admin viendo como el mensajero no debe reportar su propia ubicación
        abort_if(Impersonation::active($request), 403, 'Modo "Ver como": la ubicación no se reporta.');

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

        // La ubicación ya quedó guardada. La difusión en tiempo real (Reverb) se hace
        // DESPUÉS de responder: si ese servidor no existe o tarda, el mensajero no espera
        // ni falla su registro.
        $firstOfShift = $shift->locations()->count() === 1;

        defer(function () use ($location, $firstOfShift) {
            try {
                broadcast(new CourierLocationUpdated($location))->toOthers();
            } catch (\Throwable $e) {
                report($e);
            }

            // Con la primera ubicación del turno ya se puede asignar lo que estaba en espera
            if ($firstOfShift) {
                try {
                    app(OrderDispatchService::class)->assignWaiting();
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });

        return response()->json(['location' => $location], 201);
    }
}