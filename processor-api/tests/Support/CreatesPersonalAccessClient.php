<?php

declare(strict_types=1);

namespace Tests\Support;

use Laravel\Passport\ClientRepository;

/**
 * Passport cannot mint a personal access token without a personal access client to issue it.
 *
 * `RefreshDatabase` rebuilds the schema for every test, so the `oauth_clients` and
 * `oauth_personal_access_clients` rows that `passport:install` creates once in a real environment
 * are absent here. Without them `User::createToken()` throws
 * "Personal access client not found. Please create one." and the endpoint answers 500.
 *
 * `config/passport.php` reads `PASSPORT_PERSONAL_ACCESS_CLIENT_ID` from the environment, which
 * `.env.testing` does not set, so ClientRepository falls through to the table lookup and this row is
 * what it finds. Client secrets are not hashed in this application, so the secret persisted here is
 * the one the personal access grant reads back.
 *
 * Only tests that exercise real token issuance need this. Tests that authenticate with
 * `Passport::actingAs()` bypass token creation entirely.
 */
trait CreatesPersonalAccessClient
{
    protected function createPersonalAccessClient(): void
    {
        app(ClientRepository::class)->createPersonalAccessClient(
            null,
            'Test Personal Access Client',
            'http://localhost'
        );
    }
}
