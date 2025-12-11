<?php

namespace Database\Seeders;

use App\Factories\UserFactory;
use Illuminate\Database\Seeder;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Seed the application's database with default Admin user.
     */
    public function run(): void
    {
        // Check if admin already exists
        $adminExists = User::where('role', 'Admin')->exists();

        if (!$adminExists) {
            // Create default Admin user
            UserFactory::createAdmin([
                'name' => 'System Administrator',
                'email' => 'admin@library.com',
                'password' => 'admin123',
                'phone' => '012-3456789',
                'address' => 'TARUMT Campus',
            ]);

            $this->command->info('✅ Admin user created successfully!');
            $this->command->info('Email: admin@library.com');
            $this->command->info('Password: admin123');
            $this->command->warn('⚠️ Please change the default password after first login!');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}
