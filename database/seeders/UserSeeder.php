<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = 'admin@example.com';

        if (!User::where('email', $adminEmail)->exists()) {
            User::factory()->admin()->create([
                'name' => 'System Admin',
                'email' => $adminEmail,
                'phone' => '01000000000',
                'password' => bcrypt('Admin@123456'),
            ]);
        }
    }
}
