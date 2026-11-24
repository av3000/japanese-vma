<?php

/*
|--------------------------------------------------------------------------
| English UI labels (the source catalogue)
|--------------------------------------------------------------------------
|
| The single, code-owned source of the React client's interface text. Keys are stable dotted
| identifiers nested by area; every value is non-empty English. Never rename a key to fix a
| typo in a label: a rename counts as removing one key and adding another, and stored
| translations are not moved. Change the text and keep the key.
|
| Read it only through EnglishSourceCatalogueInterface. After adding or changing a key, run
| `php artisan translations:export-source` and commit client/src/i18n/en.generated.json.
|
*/

return [
    'account' => [
        'log_in' => 'Log In',
        'sign_up' => 'Sign Up',
        'nav_label' => 'Account',
        'status_label' => 'Account status',
        'checking_status' => 'Checking account status',
        'menu' => [
            'dashboard' => 'Dashboard',
            'log_out' => 'Log out',
        ],
    ],

    'auth' => [
        'login' => [
            'heading' => 'Welcome back',
            'email' => 'Email',
            'password' => 'Password',
            'submit' => 'Log in',
            'session_expired' => 'Your session expired. Log in again to continue.',
            'new_here' => 'New here?',
            'create_account' => 'Create an account',
        ],
    ],

    'common' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'submit' => 'Submit',
    ],

    'library' => [
        'empty' => [
            'articles' => [
                'no_match_title' => 'No articles match',
                'no_match_hint' => 'Try a shorter search, or clear the filters.',
                'filtered_title' => 'No articles match these filters',
                'filtered_hint' => 'Try fewer filters, or clear them.',
                'none_title' => 'No articles yet',
                'none_hint' => 'Articles you and others publish appear here.',
            ],
        ],
    ],
];
