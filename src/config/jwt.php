<?php

return [
    'secret' => env('JWT_SECRET', env('APP_KEY')),
    'ttl' => env('JWT_TTL', 60),
];
