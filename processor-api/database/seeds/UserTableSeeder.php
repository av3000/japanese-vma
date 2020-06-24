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
        $adminUser->email = "admin@me.com";
        $adminUser->password = Hash::make("secret123");
        $adminUser->save();

        $adminUser
            ->roles()
            ->attach(Role::where('name', 'admin')->first());

        
        $commonUser = new App\User;
        $commonUser->name  = "JohnDoe";
        $commonUser->email = "johndoe@me.com";
        $commonUser->password = Hash::make("secret123");
        $commonUser->save();

        $commonUser
            ->roles()
            ->attach(Role::where('name', 'testuser')->first());
    }
}
