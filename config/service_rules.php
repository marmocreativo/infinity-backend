<?php

return [
    // Zona horaria con la que se evalúa el horario de servicio
    'timezone' => env('SERVICE_TIMEZONE', 'America/Mexico_City'),

    // Hora límite para crear pedidos (HH:MM, 24 h)
    'order_cutoff_time' => env('ORDER_CUTOFF_TIME', '17:00'),

    // % del precio cotizado que se cobra al cancelar con pedido ya asignado
    'cancellation_fee_percent' => (float) env('CANCELLATION_FEE_PERCENT', 50),

    // % extra sobre el precio cotizado cuando el pedido se retorna al remitente
    'return_surcharge_percent' => (float) env('RETURN_SURCHARGE_PERCENT', 50),

    // Pedidos urgentes: cargo fijo y horas máximas desde la recolección
    'urgent_surcharge' => (float) env('URGENT_SURCHARGE', 280),
    'urgent_max_hours' => (int) env('URGENT_MAX_HOURS', 2),
];