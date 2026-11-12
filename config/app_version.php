<?php

// Versión más reciente de las apps frontend (cliente, admin y mensajero).
// Súbela aquí (o en APP_FRONTEND_VERSION del .env) cada vez que publiques un
// nuevo build: las apps abiertas con una versión menor pedirán actualizarse.
return [
    'frontend' => env('APP_FRONTEND_VERSION', '0.1.11'),
];
