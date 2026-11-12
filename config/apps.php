<?php

// URLs públicas de las apps frontend (se usan en correos y en "Ver como")
return [
    'urls' => [
        'cliente' => rtrim(env('FRONTEND_CLIENTE_URL', 'http://localhost:5173'), '/'),
        'mensajero' => rtrim(env('FRONTEND_MENSAJERO_URL', 'http://localhost:5175'), '/'),
        'admin' => rtrim(env('FRONTEND_ADMIN_URL', 'http://localhost:5174'), '/'),
    ],
];
