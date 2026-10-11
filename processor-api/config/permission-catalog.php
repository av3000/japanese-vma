<?php

declare(strict_types=1);

/*
 * The permission catalogue is authoritative inside the Filament panel: every panel policy maps to
 * one of these names, and holding any of them grants entry to /admin. API routes stay role-gated
 * (`checkRole:admin`), and application policies still read `AuthenticatedUser::$isAdmin`; panel
 * writes therefore need both the permission and the application rule.
 *
 * The `common` role must never hold a permission from this file - every registered user has it.
 */
return [
    'guard_name' => 'api',
    'groups' => [
        'admin' => [
            'label' => 'Administration',
            'permissions' => [
                'roles.view' => 'View roles',
                'roles.create' => 'Create roles',
                'roles.update' => 'Update roles',
                'roles.delete' => 'Delete roles',
                'users.view' => 'View users',
                'users.update' => 'Update users',
                'roles.assign' => 'Assign and remove user roles',
                'activity.view' => 'View the admin activity log',
            ],
        ],
        'content' => [
            'label' => 'Content',
            'permissions' => [
                'articles.view' => 'View articles',
                'articles.create' => 'Create articles',
                'articles.update' => 'Update articles',
                'articles.delete' => 'Delete articles',
                'catalogues.view' => 'View catalogues',
                'catalogues.create' => 'Create catalogues',
                'catalogues.update' => 'Update catalogues',
                'catalogues.delete' => 'Delete catalogues',
                'comments.view' => 'View comments',
                'comments.update' => 'Update comments',
                'comments.delete' => 'Delete comments',
            ],
        ],
        'community' => [
            'label' => 'Community',
            'permissions' => [
                'posts.view' => 'View posts',
                'posts.moderate' => 'Lock, unlock, and delete posts',
                'hashtags.manage' => 'Rename and merge hashtags',
            ],
        ],
        'study' => [
            'label' => 'Study material',
            'permissions' => [
                'study.view' => 'Browse kanji, radicals, words, and sentences',
                'sentences.moderate' => 'Edit and delete user-authored sentences',
            ],
        ],
        'operations' => [
            'label' => 'Operations',
            'permissions' => [
                'operations.view' => 'View processing, import, queue, and health pages',
                'operations.manage' => 'Retry processing and run worker checks',
            ],
        ],
    ],
];
