<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;


class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = 'admin@example.com';

        if (! User::where('email', $adminEmail)->exists()) {
            User::factory()->admin()->create([
                'name'     => 'System Admin',
                'email'    => $adminEmail,
                'phone'    => '01000000000',
                'password' => 'Admin@123456',
            ]);
        }

        $customers = [
            ['name' => 'Customer One', 'email' => 'customer1@example.com', 'phone' => '01111111111'],
            ['name' => 'Customer Two', 'email' => 'customer2@example.com', 'phone' => '01222222222'],
        ];

        foreach ($customers as $customer) {
            if (! User::where('email', $customer['email'])->exists()) {
                User::factory()->create($customer + ['password' => 'Password@123']);
            }
        }
    }
}
