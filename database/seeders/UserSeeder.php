<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@tokodaging.test'],
            ['name' => 'Owner Toko', 'password' => 'password', 'role' => UserRole::Owner, 'is_active' => true],
        );

        User::updateOrCreate(
            ['email' => 'kasir@tokodaging.test'],
            ['name' => 'Kasir 1', 'password' => 'password', 'role' => UserRole::Kasir, 'is_active' => true],
        );

        User::updateOrCreate(
            ['email' => 'kasir2@tokodaging.test'],
            ['name' => 'Kasir 2', 'password' => 'password', 'role' => UserRole::Kasir, 'is_active' => true],
        );
    }
}
