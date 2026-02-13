<?php

namespace Database\Seeders;

use App\Helpers\IdGenerator;
use Illuminate\Database\Seeder;
use App\Models\Maintenance;

class MaintenanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Maintenance::create([
            'id_maintenance' => (IdGenerator::generateUniqueMaintenanceId()),
            'id_asset' => 'PC-0002',
            'id_maintenance_officer' => 1,
            'maintenance_date' => now()->format('Y-m-d'),
            'maintenance_detail' => 'Penggantian komponen dan pembersihan unit.',
            'maintenance_cost' => 350000,
        ]);

        Maintenance::create([
            'id_maintenance' => (IdGenerator::generateUniqueMaintenanceId()),
            'id_asset' => 'PC-0003',
            'id_maintenance_officer' => 1,
            'maintenance_date' => now()->subDays(10)->format('Y-m-d'),
            'maintenance_detail' => 'Pemeriksaan rutin dan pengecekan baterai.',
            'maintenance_cost' => 150000,
        ]);
    }
}
