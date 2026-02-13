<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use DeepCopy\f013\C;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            JobProfileSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            LocationSeeder::class,
            CategorySeeder::class,
            AssetSeeder::class,
            AssetImageSeeder::class,
            RecommendationSeeder::class,
            LoanSeeder::class,
            MaintenanceSeeder::class,
            ProcurementSeeder::class,
        ]);

        User::factory()->create([
            'id_user' => 0,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'position' => 'Admin',
            'id_department' => 1,
            'id_job_profile' => 1,
            'role' => 'admin',
        ]);
    }
}
