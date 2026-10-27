<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\User as PersistenceUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Production-safe and idempotent: the system user that authors every Imported Article.
 *
 * It gets only the default `common` role every new user gets, never `admin`, and cannot sign in. Its password is the hash of a random secret that is
 * thrown away immediately, and its email sits on the reserved `.invalid` TLD, so neither the
 * login nor the password-reset flow can ever reach it.
 */
class ContentImporterUserSeeder extends Seeder
{
    public function run(): void
    {
        PersistenceUser::firstOrCreate(
            ['email' => config('content_import.system_user.email')],
            [
                'uuid' => (string) Str::uuid(),
                'name' => config('content_import.system_user.name'),
                'password' => Hash::make(Str::random(64)),
            ],
        );
    }
}
