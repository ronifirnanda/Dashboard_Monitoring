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
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@monitoring.local'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Create demo regular user
        User::firstOrCreate(
            ['email' => 'user@monitoring.local'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('user123'),
                'role' => 'user',
            ]
        );

        User::firstOrCreate(
            ['email' => 'umum3516@gmail.com'],
            [
                'name' => 'Umum 3516',
                'password' => Hash::make('umum3516jaya'),
                'role' => 'user',
            ]
        );
    }
}
