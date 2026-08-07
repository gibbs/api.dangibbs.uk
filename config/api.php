<?php

return [

    'rdap' => [
        'timeout' => env('API_RDAP_TIMEOUT', 10),
        'insecure' => env('API_RDAP_INSECURE', false),
        'experimental' => env('API_RDAP_EXPERIMENTAL', false),
    ],

];
