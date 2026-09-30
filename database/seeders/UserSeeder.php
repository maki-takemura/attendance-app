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
        User::factory()->create([
            'name' => 'user1',
            'email' => 'user1@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'admin_status' => false,
        ]);

        User::factory()->create([
            'name' => 'user2',
            'email' => 'user2@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'admin_status' => false,
        ]);

        User::factory()->create([
            'name' => 'user3',
            'email' => 'user3@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'admin_status' => true,
        ]);
    }
}
