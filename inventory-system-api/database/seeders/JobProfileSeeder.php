<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobProfile;

class JobProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            [
                'profile_name' => 'Lainnya',
                'description' => 'Pekerjaan yang tidak terdapat dalam daftar'
            ],
            [
                'profile_name' => 'Administrasi Umum',
                'description' => 'Pekerjaan administrasi dan kebutuhan perkantoran dasar'
            ],
            [
                'profile_name' => 'Desain Grafis',
                'description' => 'Pekerjaan desain grafis, editing video, dan multimedia'
            ],
            [
                'profile_name' => 'Data Analyst',
                'description' => 'Pekerjaan analisis data, statistik, dan pemodelan'
            ],
            [
                'profile_name' => 'Pemrograman',
                'description' => 'Pekerjaan development software, coding, dan testing'
            ],
            [
                'profile_name' => 'Teknisi Lab',
                'description' => 'Pekerjaan teknis laboratorium dan perawatan peralatan'
            ],
            [
                'profile_name' => 'Manajemen',
                'description' => 'Pekerjaan manajemen dan pengelolaan organisasi'
            ],
        ];

        foreach ($profiles as $profile) {
            JobProfile::create($profile);
        }
    }
}
