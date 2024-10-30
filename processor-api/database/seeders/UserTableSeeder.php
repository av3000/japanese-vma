<?php

namespace Database\Seeders;

use App\Http\Models\Role;
use App\Http\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminUser = new User;
        $adminUser->name = 'mrAdmin';
        $adminUser->email = 'admin@me.com';
        $adminUser->password = Hash::make('secret123');
        $adminUser->save();

        $adminUser
            ->roles()
            ->attach(Role::where('name', 'admin')->first());

        $commonUser = new User;
        $commonUser->name = 'JohnDoe';
        $commonUser->email = 'johndoe@me.com';
        $commonUser->password = Hash::make('secret123');
        $commonUser->save();

        $commonUser
            ->roles()
            ->attach(Role::where('name', 'testuser')->first());
    }
}
