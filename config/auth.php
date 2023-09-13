<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'tbl_registration',
    ],

    'guards' => [
        'api' => [
            'driver' => 'jwt',
            'provider' => 'tbl_registration',
        ],
    ],

    'providers' => [
        'tbl_registration' => [
            'driver' => 'eloquent',
            'model' => \App\Models\Registration::class
        ]
    ]
];

?>