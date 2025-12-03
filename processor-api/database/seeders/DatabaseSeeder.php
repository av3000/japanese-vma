<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // TODO: try to setup project from scratch and fix these as required to make it work
        $this->call([
            RoleSeeder::class
        ]);
        $this->call([UserTableSeeder::class])
        // $this->call(UserTableSeeder::class);
        // $this->call(ObjectTemplatesTableSeeder::class);
        // $this->call(ArticlesTableSeeder::class);
        // $this->call(CustomListsTableSeeder::class);
    }
}
