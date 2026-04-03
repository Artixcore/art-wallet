<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->command?->warn('AdminUserSeeder skipped: set ADMIN_EMAIL and ADMIN_PASSWORD in your .env file.');

            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('admin.name'),
                'password' => $password,
                'email_verified_at' => now(),
                'is_admin' => true,
            ]
        );

        $this->command?->info("Administrator ready: {$email}");
    }
}
