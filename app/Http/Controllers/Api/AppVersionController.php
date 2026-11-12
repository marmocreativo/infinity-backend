<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class AppVersionController extends Controller
{
    /** Versión vigente de las apps frontend (pública, sin caché) */
    public function show()
    {
        return response()
            ->json(['version' => config('app_version.frontend')])
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
