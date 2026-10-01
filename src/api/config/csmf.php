<?php

/*
|--------------------------------------------------------------------------
| CSMF-MRS application settings
|--------------------------------------------------------------------------
|
| Read env() only here, so values survive `php artisan config:cache`.
|
*/

return [

    // Defaults for `php artisan csmf:create-sysadmin` (the password is
    // always typed at a prompt, never stored in the environment).
    'sysadmin' => [
        'name' => env('SYSADMIN_NAME'),
        'email' => env('SYSADMIN_EMAIL'),
    ],

];
