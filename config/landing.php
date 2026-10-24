<?php

return [
    // URL de la app de clínicas (botón principal). Si está vacía, el botón baja al formulario.
    'client_url' => env('LANDING_CLIENT_URL'),

    // URL de la app de mensajeros (enlace discreto del pie de página)
    'courier_url' => env('LANDING_COURIER_URL'),

    // Solo dígitos con lada de país, p. ej. 5215512345678
    'whatsapp' => env('LANDING_WHATSAPP'),

    // Se muestran solo si están configurados
    'phone' => env('LANDING_PHONE'),
    'email' => env('LANDING_EMAIL'),
];