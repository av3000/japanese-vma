<?php

$privateKey = env('PASSPORT_PRIVATE_KEY');
$publicKey = env('PASSPORT_PUBLIC_KEY');

if (($privateKey === null || $privateKey === '') && env('PASSPORT_PRIVATE_KEY_B64')) {
    $privateKey = base64_decode((string) env('PASSPORT_PRIVATE_KEY_B64'), true) ?: null;
}

if (($publicKey === null || $publicKey === '') && env('PASSPORT_PUBLIC_KEY_B64')) {
    $publicKey = base64_decode((string) env('PASSPORT_PUBLIC_KEY_B64'), true) ?: null;
}

return [
    'private_key' => $privateKey,
    'public_key' => $publicKey,

    'personal_access_client' => [
        'id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],
];
