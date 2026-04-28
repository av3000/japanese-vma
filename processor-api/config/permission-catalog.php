<?php

declare(strict_types=1);

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
    ],
];
