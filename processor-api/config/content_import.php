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
    | Run lock
    |--------------------------------------------------------------------------
    |
    | Only one Import Run per Content Source runs at a time; the run takes a cache lock to
    | make sure. A lock is only shared between hosts that use the same cache store, so every
    | host that may run an import must point this at the same store (Redis in production).
    | Null uses the default cache store.
    |
    */

    'lock_store' => env('CONTENT_IMPORT_LOCK_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Run limits
    |--------------------------------------------------------------------------
    |
    | max_created_per_run  Imported Articles one run may create. The schedule runs once a
    |                      day, so this is the daily volume unless someone also runs it by hand.
    | max_listed       Listed articles one run may look at, filtered or not.
    | min_lead_length  Leads shorter than this (characters) are one-line bulletins; skipped.
    | excluded_genres  An article is skipped only when every one of its genres is listed here.
    | stalled_after_runs  Warn when this many successful runs in a row created nothing.
    |
    | A source entry overrides any of these and names its adapter class.
    |
    */

    'defaults' => [
        'max_created_per_run' => 10,
        'max_listed' => 100,
        'min_lead_length' => 60,
        'excluded_genres' => [],
        'stalled_after_runs' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP manners
    |--------------------------------------------------------------------------
    */

    'http' => [
        'request_interval_ms' => (int) env('CONTENT_IMPORT_REQUEST_INTERVAL_MS', 1000),
        'timeout_seconds' => 15,
    ],

    'sources' => [

        // NHK News: excerpt only (headline, NHK's own lead, link). Read from the Google News
        // sitemap and each page's NewsArticle JSON-LD on news.web.nhk; api.web.nhk is off limits
        // because its robots.txt disallows every user agent.
        'nhk-news' => [
            'adapter' => App\Infrastructure\ContentImport\Sources\Nhk\NhkNewsAdapter::class,
            'sitemap_url' => 'https://news.web.nhk/sitemap/sitemap-news-nationwide-article.xml',
            // NHK truncates its leads at about 100 characters, so the bar for "too short to be
            // more than a bulletin" sits lower than the default.
            'min_lead_length' => 40,
            'excluded_genres' => ['気象・災害'],
        ],

    ],

];
