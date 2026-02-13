<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'department_name' => 'UPT-Lab',
            ],
            [
                'department_name' => 'IT',
            ],
            [
                'department_name' => 'Administrasi',
            ],
            [
                'department_name' => 'Keuangan',
            ],
            [
                'department_name' => 'Pemasaran',
            ],
            [
                'department_name' => 'Produksi',
            ],
            [
                'department_name' => 'Penelitian dan Pengembangan',
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}
