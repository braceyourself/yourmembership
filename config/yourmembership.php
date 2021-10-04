<?php
return [
    'accounts' => [
        'key' => [
            'client_id'   => env('YOURMEMBERSHIP_CLIENT_ID'),
            'api_version' => env('YOURMEMBERSHIP_API_VERSION', null),
            'api_key'     => env('YOURMEMBERSHIP_PUBLIC_KEY'),
            'private_key' => env('YOURMEMBERSHIP_PRIVATE_KEY'),
            'sa_passcode' => env('YOURMEMBERSHIP_SA_PASSCODE'),
        ]
    ],

    'classmap' => [
        'registration' => \Braceyourself\Yourmembership\Models\Registration::class,
        'event'        => \Braceyourself\Yourmembership\Models\Event::class,
        'person'       => \Braceyourself\Yourmembership\Models\Person::class,
    ]
];
