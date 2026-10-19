<?php

return [
    // Tarifas de ejemplo: ajústalas a tu modelo de negocio (o vía .env)
    'base_fee' => (float) env('PRICING_BASE_FEE', 80),
    'per_km' => (float) env('PRICING_PER_KM', 12),
    'minimum' => (float) env('PRICING_MINIMUM', 100),

    // Si OSRM falla, distancia en línea recta × este factor
    'fallback_route_factor' => 1.3,
];