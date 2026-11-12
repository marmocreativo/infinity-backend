<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceTokenRequest;
use App\Models\DeviceToken;
use App\Support\Impersonation;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(StoreDeviceTokenRequest $request)
    {
        // En "Ver como" no se registra el navegador del admin como dispositivo del usuario
        if (Impersonation::active($request)) {
            return response()->json(['message' => 'Modo "Ver como": token no registrado.']);
        }

        // El token es único por navegador/dispositivo: si ya existía (por ejemplo,
        // registrado antes por otro usuario en el mismo navegador), se reasigna
        // al usuario que tiene la sesión iniciada ahora.
        DeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => $request->user()->id,
                'platform' => $request->platform,
            ]
        );

        return response()->json(['message' => 'Token registrado.']);
    }

    public function destroy(Request $request)
    {
        $request->validate(['token' => ['required', 'string']]);

        $request->user()->deviceTokens()->where('token', $request->token)->delete();

        return response()->json(['message' => 'Token eliminado.']);
    }
}