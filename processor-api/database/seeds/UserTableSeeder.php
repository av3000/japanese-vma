<?php

use Illuminate\Database\Seeder;
use App\Role;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminUser = new App\User;
        $adminUser->name        = "mrAdmin";
        $adminUser->email = "admin@admin.com";
        $adminUser->password = Hash::make("secret123");
        $adminUser->save();

        $adminUser
            ->roles()
            ->attach(Role::where('name', 'admin')->first());
    }
}
