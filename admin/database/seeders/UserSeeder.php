<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin Toko',
            'email' => 'admin@toko.local',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'phone' => '081234567890',
            'address' => 'Jl. Admin No. 1',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Kasir 1',
            'email' => 'kasir1@toko.local',
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'phone' => '081234567891',
            'address' => 'Jl. Kasir No. 1',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Kasir 2',
            'email' => 'kasir2@toko.local',
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'phone' => '081234567892',
            'address' => 'Jl. Kasir No. 2',
            'is_active' => true,
        ]);
    }
}
