<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Metas comerciales del dashboard
    |--------------------------------------------------------------------------
    |
    | Cambia solo estas líneas cuando negocio actualice objetivos.
    | La meta mensual se deriva como semanal x 4.
    | La meta diaria del dashboard usa lunes a viernes.
    | Cuando no se define una diaria explícita, se deriva como semanal / 5.
    | Las metas semanales que no tengan diaria explícita deben seguir siendo
    | divisibles entre 5 para conservar metas diarias enteras.
    |
    */
    'weekly_per_executive' => [
        'contactado' => 100,
        'negociacion' => 10,
        'acuerdo_aceptado' => 2,
        'reprogramado' => 100,
    ],

    'daily_per_executive' => [
        'contactado' => 20,
        'negociacion' => 2,
        'acuerdo_aceptado' => 1,
        'reprogramado' => 20,
    ],
];
