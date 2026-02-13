<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'id_user' => 1,
            'name' => 'Administrator',
            'email' => 'admin@kabarpangan.com',
            'position' => 'IT Manager',
            'id_department' => 2,
            'id_job_profile' => 5,
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'id_user' => 2,
            'name' => 'Andi Wijaya',
            'email' => 'andi@kabarpangan.com',
            'position' => 'Lab Technician',
            'id_department' => 1,
            'id_job_profile' => 6,
            'password' => Hash::make('password'),
            'role' => 'moderator',
        ]);

        User::factory()->create([
            'id_user' => 3,
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@kabarpangan.com',
            'position' => 'Staff Administrasi',
            'id_department' => 3,
            'id_job_profile' => 2,
            'password' => Hash::make('password'),
            'role' => 'employee',
        ]);

        User::factory()->create([
            'id_user' => 4,
            'name' => 'Budi Santoso',
            'email' => 'budi@kabarpangan.com',
            'position' => 'Graphic Designer',
            'id_department' => 5,
            'id_job_profile' => 3,
            'password' => Hash::make('password'),
            'role' => 'employee',
        ]);

        User::factory()->create([
            'id_user' => 5,
            'name' => 'Dewi Lestari',
            'email' => 'dewi@kabarpangan.com',
            'position' => 'Data Analyst',
            'id_department' => 7,
            'id_job_profile' => 4,
            'password' => Hash::make('password'),
            'role' => 'employee',
        ]);
    }
}
