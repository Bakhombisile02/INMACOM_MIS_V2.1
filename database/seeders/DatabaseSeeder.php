<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ManagementAreasSeeder::class,
            DomainDataSeeder::class,
            DDRIteration2ReferenceDataSeeder::class,
            ExcelDataSeeder::class,
            StationSqlSeeder::class,
            UpdateHydrologyDataJuly2019Seeder::class,
        ]);

        // Seed default test user if they do not exist
        if (! User::where('email', 'test@example.com')->exists()) {
            User::create([
                'id' => (string) Str::uuid(),
                'display_name' => 'Test User',
                'email' => 'test@example.com',
                'role' => User::ROLE_ADMIN,
                'firebase_uid' => 'seed-test-user-uid',
            ]);
        }
    }
}
