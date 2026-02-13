<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recommendation;

class RecommendationSeeder extends Seeder
{
    public function run(): void
    {
        Recommendation::create([
            'id_asset' => 'PC-0001',
            'id_job_profile' => 1,
        ]);

        Recommendation::create([
            'id_asset' => 'PC-0002',
            'id_job_profile' => 2,
        ]);

        Recommendation::create([
            'id_asset' => 'PC-0003',
            'id_job_profile' => 3,
        ]);
    }
}
