<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'email' => 'admin@nfos.com',
                'name' => 'NFOS Demo Admin',
                'password' => 'admin123',
            ],
            [
                'email' => 'admin@merzi.test',
                'name' => 'MERZi Demo Admin',
                'password' => 'password123',
            ],
        ];

        foreach ($admins as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($admin['password']),
                    'role' => 'admin',
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}


