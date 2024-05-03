<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = 'test1234';

        /** Create Super Admin */
        User::create([
            'name' => 'Super Admin',
            'email' => 'super@onedashboard.com',
            'phone_number' => '+62818181xxxxx',
            'password' => bcrypt($password),
        ]);
    }
}
