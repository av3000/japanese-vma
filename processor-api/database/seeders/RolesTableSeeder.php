<?php

namespace Database\Seeders;

use App\Http\Models\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminRole = new Role;
        $adminRole->name = 'admin';
        $adminRole->description = 'Administrator';
        $adminRole->save();

        $testuserRole = new Role;
        $testuserRole->name = 'testuser';
        $testuserRole->description = 'Test User';
        $testuserRole->save();
    }
}
