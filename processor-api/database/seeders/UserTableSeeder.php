<?php

namespace Database\Seeders;

use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\User as PersistenceUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminEmail = 'admin@me.com';
        $adminUser = PersistenceUser::firstOrCreate(
            ['email' => $adminEmail],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'mrAdmin',
                'password' => Hash::make('secret123'),
            ]
        );

        $adminUser->assignRole(UserRole::ADMIN->value);
        $adminUser->assignRole(UserRole::COMMON->value);
        $adminUser->assignRole('testuser');


        $commonEmail = 'johndoe@me.com';
        $commonUser = PersistenceUser::firstOrCreate(
            ['email' => $commonEmail],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'JohnDoe',
                'password' => Hash::make('secret123'),
            ]
        );

        $adminUser->assignRole(UserRole::COMMON->value);
        $commonUser->assignRole('testuser');
    }
}
