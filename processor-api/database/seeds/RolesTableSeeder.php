<?php

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
        $adminRole = new App\Role;
        $adminRole->name        = "admin";
        $adminRole->description = "Administrator";
        $adminRole->save(); 

        $testuserRole = new App\Role;
        $testuserRole->name        = "testuser";
        $testuserRole->description = "Test User";
        $testuserRole->save(); 
    }
}
