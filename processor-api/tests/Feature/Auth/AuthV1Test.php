<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Shared\Enums\CatalogueType;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\Support\CreatesPersonalAccessClient;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * RET-AUTH-01 pins the v1 Auth contract before the legacy session routes are removed.
 *
 * The legacy `register`/`login`/`logout`/`user` endpoints are retired by this slice, and v1 had no
 * feature coverage at all beforehand - only the unit tests around PassportCurrentUserProvider. This
 * suite is the regression net that makes the removal safe, so it asserts the wire contract the
 * generated frontend client is built from rather than just happy-path status codes.
 */
class AuthV1Test extends TestCase
{
    use CreatesPersonalAccessClient, RefreshDatabase, SeedsBaselineData;

    /**
     * A password that satisfies every rule on RegisterRequest: 8+ characters, mixed case, a digit
     * and a symbol. `uncompromised()` also consults an external breach corpus, which is why this is
     * a nonsense string rather than anything resembling a real-world password.
     */
    private const VALID_PASSWORD = 'Str0ng!Passw0rd#42';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
        $this->createPersonalAccessClient();
    }

    // ========================================
    // register
    // ========================================

    public function test_register_returns_created_with_the_token_bearing_contract(): void
    {
        $response = $this->postJson('/api/v1/register', $this->registrationPayload());

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id', 'uuid', 'name', 'email', 'roles', 'is_admin', 'created_at',
                    'access_token', 'token_type',
                ],
            ]);

        self::assertTrue($response->json('success'));
        self::assertSame('newcomer', $response->json('data.name'));
        self::assertSame('Bearer', $response->json('data.token_type'));
        self::assertNotEmpty($response->json('data.access_token'));
        self::assertFalse($response->json('data.is_admin'));

        // The resource must never leak the hash, and the token belongs in `access_token` only.
        $response->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', ['name' => 'newcomer', 'email' => 'newcomer@gmail.com']);
    }

    /**
     * The legacy controller inlined four `CustomList` inserts after creating the user. That side
     * effect is the one piece of behaviour that could disappear silently when the legacy method is
     * deleted, so it is asserted explicitly against its v1 owner.
     */
    public function test_register_creates_the_four_default_catalogues(): void
    {
        $this->postJson('/api/v1/register', $this->registrationPayload())->assertCreated();

        $user = User::where('email', 'newcomer@gmail.com')->firstOrFail();

        $catalogues = Catalogue::where('user_id', $user->id)
            ->orderBy('type')
            ->get(['type', 'title', 'description', 'publicity']);

        self::assertCount(4, $catalogues, 'a new account must start with its four "Known" catalogues');

        self::assertSame(
            [
                CatalogueType::KNOWN_RADICALS->value,
                CatalogueType::KNOWN_KANJIS->value,
                CatalogueType::KNOWN_WORDS->value,
                CatalogueType::KNOWN_SENTENCES->value,
            ],
            $catalogues->pluck('type')->map(static fn ($type): int => (int) $type)->all()
        );

        self::assertSame(
            ['Known Radicals', 'Known Kanjis', 'Known Words', 'Known Sentences'],
            $catalogues->pluck('title')->all()
        );

        foreach ($catalogues as $catalogue) {
            self::assertFalse((bool) $catalogue->publicity, 'default catalogues are private');
        }
    }

    /**
     * v1 is deliberately stricter than the legacy `min:6` rule. Retiring `POST api/register` closes
     * a bypass around this policy, so the policy itself is worth pinning.
     */
    public function test_register_rejects_a_password_that_is_too_weak(): void
    {
        $this->postJson('/api/v1/register', $this->registrationPayload(['password' => 'abc123', 'password_confirmation' => 'abc123']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_register_requires_a_confirmed_password(): void
    {
        $this->postJson('/api/v1/register', $this->registrationPayload(['password_confirmation' => self::VALID_PASSWORD.'mismatch']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_register_rejects_a_duplicate_name(): void
    {
        User::factory()->create(['name' => 'newcomer']);

        $this->postJson('/api/v1/register', $this->registrationPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_register_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'newcomer@gmail.com']);

        $this->postJson('/api/v1/register', $this->registrationPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_register_rejects_a_name_with_disallowed_characters(): void
    {
        $response = $this->postJson('/api/v1/register', $this->registrationPayload(['name' => 'bad name!']));

        $response->assertUnprocessable()->assertJsonValidationErrors('name');

        self::assertSame(
            'Username can only contain letters, numbers, underscores, and hyphens.',
            $response->json('errors.name.0')
        );
    }

    public function test_register_does_not_persist_anything_when_validation_fails(): void
    {
        $this->postJson('/api/v1/register', $this->registrationPayload(['name' => 'bad name!']))
            ->assertUnprocessable();

        $this->assertDatabaseMissing('users', ['email' => 'newcomer@gmail.com']);
        self::assertSame(0, Catalogue::count());
    }

    // ========================================
    // login
    // ========================================

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'member@gmail.com',
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'member@gmail.com',
            'password' => self::VALID_PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'uuid', 'name', 'email', 'roles', 'is_admin', 'created_at', 'access_token', 'token_type'],
            ]);

        self::assertSame($user->id, $response->json('data.id'));
        self::assertSame($user->uuid, $response->json('data.uuid'));
        self::assertSame('Bearer', $response->json('data.token_type'));
        self::assertNotEmpty($response->json('data.access_token'));
    }

    public function test_login_rejects_a_wrong_password_with_unauthorized(): void
    {
        User::factory()->create([
            'email' => 'member@gmail.com',
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'member@gmail.com',
            'password' => 'not-the-password',
        ]);

        $response->assertUnauthorized();
        self::assertSame('Invalid credentials', $response->json('title'));
        self::assertSame('Invalid email or password', $response->json('errorMessage'));
    }

    /**
     * An unknown email and a wrong password must be indistinguishable, or the endpoint becomes a
     * user-enumeration oracle.
     */
    public function test_login_does_not_distinguish_an_unknown_email_from_a_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'member@gmail.com',
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $wrongPassword = $this->postJson('/api/v1/login', [
            'email' => 'member@gmail.com',
            'password' => 'not-the-password',
        ]);

        $unknownEmail = $this->postJson('/api/v1/login', [
            'email' => 'nobody@gmail.com',
            'password' => self::VALID_PASSWORD,
        ]);

        $unknownEmail->assertUnauthorized();

        self::assertSame($wrongPassword->json('title'), $unknownEmail->json('title'));
        self::assertSame($wrongPassword->json('errorMessage'), $unknownEmail->json('errorMessage'));
    }

    public function test_login_validates_the_email_field(): void
    {
        $this->postJson('/api/v1/login', ['password' => self::VALID_PASSWORD])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->postJson('/api/v1/login', ['email' => 'not-an-email', 'password' => self::VALID_PASSWORD])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    // ========================================
    // me
    // ========================================

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'member', 'email' => 'member@gmail.com']);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data' => ['id', 'uuid', 'name', 'email', 'roles', 'is_admin', 'created_at']]);

        self::assertSame($user->id, $response->json('data.id'));
        self::assertSame($user->uuid, $response->json('data.uuid'));
        self::assertSame('member', $response->json('data.name'));
        self::assertSame('member@gmail.com', $response->json('data.email'));
        self::assertFalse($response->json('data.is_admin'));
    }

    /**
     * `AuthUserResource` wraps the token pair in `when()` so Scramble emits both fields as optional
     * and the generated client stops promising an `access_token` on a `me` payload. If that ever
     * regresses to an unconditional key, `client/src/api/auth/session.ts` would start persisting an
     * empty credential, so the absence is asserted rather than assumed.
     */
    public function test_me_omits_the_token_pair(): void
    {
        Passport::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/me')->assertOk();

        $response->assertJsonMissingPath('data.access_token');
        $response->assertJsonMissingPath('data.token_type');
    }

    public function test_me_rejects_an_unauthenticated_request(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    // ========================================
    // logout
    // ========================================

    public function test_logout_revokes_the_presented_token(): void
    {
        $user = User::factory()->create([
            'email' => 'member@gmail.com',
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $accessToken = $this->postJson('/api/v1/login', [
            'email' => 'member@gmail.com',
            'password' => self::VALID_PASSWORD,
        ])->assertOk()->json('data.access_token');

        $response = $this->withToken($accessToken)->postJson('/api/v1/logout');

        $response->assertOk();
        self::assertSame('Successfully logged out', $response->json('message'));

        self::assertSame(
            1,
            $user->tokens()->where('revoked', true)->count(),
            'the token presented on the request must be revoked'
        );
    }

    public function test_a_revoked_token_no_longer_authenticates(): void
    {
        User::factory()->create([
            'email' => 'member@gmail.com',
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $accessToken = $this->postJson('/api/v1/login', [
            'email' => 'member@gmail.com',
            'password' => self::VALID_PASSWORD,
        ])->assertOk()->json('data.access_token');

        $this->withToken($accessToken)->postJson('/api/v1/logout')->assertOk();

        $this->withToken($accessToken)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_logout_rejects_an_unauthenticated_request(): void
    {
        $this->postJson('/api/v1/logout')->assertUnauthorized();
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'newcomer',
            'email' => 'newcomer@gmail.com',
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
        ], $overrides);
    }
}
