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

    /*
    |--------------------------------------------------------------------------
    | Run limits
    |--------------------------------------------------------------------------
    |
    | daily_cap        Imported Articles one run may create.
    | max_listed       Listed articles one run may look at, filtered or not.
    | min_lead_length  Leads shorter than this (characters) are one-line bulletins; skipped.
    | excluded_genres  An article is skipped only when every one of its genres is listed here.
    |
    | A source entry overrides any of these and names its adapter class.
    |
    */

    'defaults' => [
        'daily_cap' => 10,
        'max_listed' => 100,
        'min_lead_length' => 60,
        'excluded_genres' => [],
    ],

    'sources' => [],

];
