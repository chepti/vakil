<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the first admin user.
 * Run once on production: php artisan db:seed --class=AdminSeeder
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('role', 'admin')->exists()) {
            $this->command->info('Admin already exists — skipping.');
            return;
        }

        $email    = $this->command->ask('Admin email?', 'admin@vakil.chepti.com');
        $name     = $this->command->ask('Admin name?', 'מנהל המערכת');
        $password = $this->command->secret('Admin password?');

        User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        $this->command->info("✅ Admin created: {$email}");
        $this->command->info('Now log in and complete onboarding to add yourself to the family tree.');
    }
}
