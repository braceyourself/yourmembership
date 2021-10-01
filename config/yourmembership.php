<?php
return [
    'accounts' => [
        'key' => [
            'api_version'    => null,
            'api_key'        => env('YOURMEMBERSHIP_PUBLIC_KEY'),
            'private_key'    => env('YOURMEMBERSHIP_PRIVATE_KEY'),
            'sa_passcode'    => env('YOURMEMBERSHIP_SA_PASSCODE'),
            'usermeta_class' => null,
            'user_class'     => null,
        ]
    ],
];
