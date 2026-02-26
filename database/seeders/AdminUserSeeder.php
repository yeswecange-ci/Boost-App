<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@boostapp.com',
            'password' => Hash::make('password'),
            'is_active'=> true,
        ]);

        $admin->assignRole('admin');
    }
}