<?php

return [
    // MXN por kilómetro, impuestos incluidos
    'per_km' => (float) env('PRICING_PER_KM', 13.92),
    'base_fee' => (float) env('PRICING_BASE_FEE', 0),
    'minimum' => (float) env('PRICING_MINIMUM', 0),

    // Si OSRM falla, distancia en línea recta × este factor
    'fallback_route_factor' => 1.3,
];