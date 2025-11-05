<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates the primary admin user for the application.
     */
    public function run(): void
    {
        $this->command->info('Creating admin user...');

        // Check if admin already exists
        $existingAdmin = User::where('email', 'gasser.elshewaikh@gmail.com')->first();

        if ($existingAdmin) {
            $this->command->warn('Admin user already exists. Updating credentials...');

            $existingAdmin->update([
                'name' => 'Gasser Elshewaikh',
                'password' => Hash::make('Hamo1510@Rayan146'),
                'is_admin' => true,
                'role' => 'admin',
                'is_active' => true,
                'is_blocked' => false,
                'email_verified_at' => now(),
            ]);

            $this->command->info('✅ Admin user credentials updated successfully!');
        } else {
            User::create([
                'name' => 'Gasser Elshewaikh',
                'email' => 'gasser.elshewaikh@gmail.com',
                'password' => Hash::make('Hamo1510@Rayan146'),
                'is_admin' => true,
                'role' => 'admin',
                'is_active' => true,
                'is_blocked' => false,
                'email_verified_at' => now(),
            ]);

            $this->command->info('✅ Admin user created successfully!');
        }

        $this->command->info('');
        $this->command->info('Admin credentials:');
        $this->command->info('Email: gasser.elshewaikh@gmail.com');
        $this->command->info('Password: Hamo1510@Rayan146');
        $this->command->info('');
    }
}
