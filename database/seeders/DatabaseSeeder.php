<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@heavengatecamp.com')],
            [
                'name' => env('ADMIN_NAME', 'Heaven Gate Admin'),
                'password' => env('ADMIN_PASSWORD', 'change-me-now'),
                'role' => UserRole::Admin,
            ],
        );

        $this->call([
            SettingsSeeder::class,
            CatalogSeeder::class,
            ContentSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DemoBookingsSeeder::class);
        }
    }
}
