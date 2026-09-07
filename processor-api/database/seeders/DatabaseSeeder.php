<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Production-safe, idempotent reference data. Every environment needs these rows.
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(ObjectTemplatesTableSeeder::class);
        // The seeders below create sample/dev data; keep them opt-in.
        // UserTableSeeder runs through `php artisan app:setup --with-dev-users`.
        // $this->call(ArticlesTableSeeder::class);
        // $this->call(CustomListsTableSeeder::class);
    }
}
