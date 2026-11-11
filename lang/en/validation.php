<?php

/*
|--------------------------------------------------------------------------
| Validation Language Lines (partial override)
|--------------------------------------------------------------------------
|
| Only the password messages are overridden here so students see plain,
| specific guidance. All other messages still come from Laravel's own
| translation files.
|
*/

return [
    'password' => [
        'letters' => 'The password must contain at least one letter.',
        'mixed' => 'The password must contain both uppercase and lowercase letters.',
        'numbers' => 'The password must contain at least one number.',
        'symbols' => 'The password must contain at least one symbol.',
        'uncompromised' => 'This password has appeared in a data breach. Please choose a different password.',
    ],
];
