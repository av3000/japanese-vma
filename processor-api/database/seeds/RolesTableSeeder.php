<?php

use Illuminate\Database\Seeder;
use App\Http\Models\Role;

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
        $adminRole->name        = "admin";
        $adminRole->description = "Administrator";
        $adminRole->save(); 

        $testuserRole = new Role;
        $testuserRole->name        = "testuser";
        $testuserRole->description = "Test User";
        $testuserRole->save(); 
    }
}
