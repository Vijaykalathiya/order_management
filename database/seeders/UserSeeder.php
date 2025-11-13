<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Create Admin User
        User::create([
            'name' => 'Admin',
            'email' => 'admin',
            'password' => Hash::make('admin'), // Change this!
            'role' => 'admin',
        ]);

        // Create Cashier User
        User::create([
            'name' => 'Cashier',
            'email' => 'cashier',
            'password' => Hash::make('cashier'),
            'role' => 'cashier',
        ]);
    }
}
