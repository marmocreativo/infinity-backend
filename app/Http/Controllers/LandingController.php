<?php

namespace App\Http\Controllers;

class LandingController extends Controller
{
    public function __invoke()
    {
        $cutoff = config('service_rules.order_cutoff_time', '17:00');
        [$h, $m] = array_map('intval', explode(':', $cutoff));

        return view('landing', [
            'perKm' => number_format((float) config('pricing.per_km'), 2),
            'cutoffLabel' => sprintf('%d:%02d %s', $h % 12 ?: 12, $m, $h >= 12 ? 'p. m.' : 'a. m.'),
            'urgentHours' => (int) config('service_rules.urgent_max_hours', 2),
            'contact' => config('landing'),
        ]);
    }
}