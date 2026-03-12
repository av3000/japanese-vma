<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     */
    public function run(): void
    {
        // Keep production-safe seeds idempotent. Create required roles (ex: "common") only.
        $this->call(RoleSeeder::class);
        // The seeders below create sample/dev data; keep them opt-in.
        // $this->call(UserTableSeeder::class);
        // $this->call(ObjectTemplatesTableSeeder::class);
        // $this->call(ArticlesTableSeeder::class);
        // $this->call(CustomListsTableSeeder::class);
    }
}
