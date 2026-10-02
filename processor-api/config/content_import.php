<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System author
    |--------------------------------------------------------------------------
    |
    | Every Imported Article is authored by one seeded system user, looked up by this email.
    | The `.invalid` TLD is reserved (RFC 2606), so a password reset for it reaches nobody.
    |
    */

    'system_user' => [
        'email' => env('CONTENT_IMPORT_SYSTEM_USER_EMAIL', 'content-importer@system.invalid'),
        'name' => 'Content Importer',
    ],

];
