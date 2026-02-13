<?php

namespace Database\Seeders;

use App\Helpers\IdGenerator;
use Illuminate\Database\Seeder;
use App\Models\Procurement;

class ProcurementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Procurement::create([
            'id_procurement' => (IdGenerator::generateUniqueProcurementId()),
            'id_requester' => 1,
            'id_job_profile_target' => 2,
            'item_name' => 'Laptop Video Editing',
            'desired_specifications' => 'i7, 16GB RAM, 512GB SSD, GPU dedicated',
            'quantity' => 1,
            'reason' => 'Untuk kebutuhan editing video divisi media.',
            'request_date' => '2025-01-10',
            'procurement_status' => 'Diajukan',
            'id_approver' => 1,
            'action_date' => '2025-01-09',
            'approver_notes' => 'Disetujui',
        ]);

        Procurement::create([
            'id_procurement' => (IdGenerator::generateUniqueProcurementId()),
            'id_requester' => 3,
            'id_job_profile_target' => 1,
            'item_name' => 'Webcam FullHD',
            'desired_specifications' => '1080p, auto lighting correction',
            'quantity' => 2,
            'reason' => 'Untuk kebutuhan meeting dan presentasi remote.',
            'request_date' => '2025-01-08',
            'procurement_status' => 'Disetujui',
            'id_approver' => 1,
            'action_date' => '2025-01-09',
            'approver_notes' => 'Disetujui',
        ]);
    }
}
