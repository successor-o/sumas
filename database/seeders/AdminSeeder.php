<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Creates the default admin account.
     * Credentials come from .env:
     *   ADMIN_USERNAME=admin
     *   ADMIN_PASSWORD=sumas@admin2025
     */
    public function run(): void
    {
        $username = env('ADMIN_USERNAME', 'admin');
        $password = env('ADMIN_PASSWORD', 'sumas@admin2025');

        // Don't create a duplicate
        if (User::where('username', $username)->exists()) {
            $this->command->info("Admin '{$username}' already exists. Skipping.");
            return;
        }

        User::create([
            'name'     => 'SUMAS Administrator',
            'username' => $username,
            'email'    => 'admin@sumas.edu.ng',
            'password' => Hash::make($password),
            'role'     => 'admin',
            'status'   => 'Approved',
            'verified' => true,
        ]);

        $this->command->info("✅ Admin account created: username={$username}");
        $this->command->info("   Login at: /admin/login");
    }
}
