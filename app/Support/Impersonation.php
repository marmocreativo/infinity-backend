<?php

namespace App\Support;

use Illuminate\Http\Request;

class Impersonation
{
    /** ¿La petición viene de una sesión "Ver como" iniciada por un administrador? */
    public static function active(Request $request): bool
    {
        $token = $request->user()?->currentAccessToken();

        return $token && isset($token->name) && str_starts_with($token->name, 'impersonation:');
    }
}
