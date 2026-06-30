<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@nisoya.test'],
            [
                'name' => 'Nisoya Admin',
                'username' => 'admin',
                'password' => Hash::make('nisoya1234'),
                'email_verified_at' => now(),
                'role' => UserRole::Admin,
                'is_verified' => true,
                'country_code' => 'DE',
                'preferred_currency' => 'EUR',
            ]
        );

        $this->command->info('Admin hazır: admin@nisoya.test / nisoya1234');
    }
}
