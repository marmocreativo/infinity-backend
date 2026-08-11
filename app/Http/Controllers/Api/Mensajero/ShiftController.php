<?php

namespace App\Http\Controllers\Api\Mensajero;

use App\Http\Controllers\Controller;
use App\Models\CourierShift;
use Illuminate\Http\Request;
use App\Services\PushNotificationService;

class ShiftController extends Controller
{
    public function current(Request $request)
    {
        $shift = CourierShift::where('courier_id', $request->user()->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        return response()->json([
            'on_duty' => $shift !== null,
            'shift' => $shift,
        ]);
    }

    public function start(Request $request, PushNotificationService $push)
    {
        $existing = CourierShift::where('courier_id', $request->user()->id)
            ->whereNull('ended_at')
            ->first();

        abort_if($existing, 422, 'Ya tienes un turno activo.');

        $shift = CourierShift::create([
            'courier_id' => $request->user()->id,
            'started_at' => now(),
        ]);

        $request->user()->courierProfile->update(['is_on_duty' => true]);

        $push->sendToRole('admin', 'Mensajero en labores', "{$request->user()->name} inició su turno.", [
            'courier_id' => (string) $request->user()->id,
            'type' => 'courier_shift_started',
        ]);

        return response()->json(['shift' => $shift], 201);
    }

    public function end(Request $request)
    {
        $shift = CourierShift::where('courier_id', $request->user()->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        abort_unless($shift, 422, 'No tienes un turno activo.');

        $shift->update(['ended_at' => now()]);

        $request->user()->courierProfile->update(['is_on_duty' => false]);

        return response()->json(['shift' => $shift]);
    }
}