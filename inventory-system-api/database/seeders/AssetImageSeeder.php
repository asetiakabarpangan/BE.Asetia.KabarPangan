<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssetImage;

class AssetImageSeeder extends Seeder
{
    public function run(): void
    {
        AssetImage::insert([
            [
                'id_asset' => 'Lap-0001',
                'filename' => 'Lap-0001-001.jpg',
                'image_url' => 'storage/app/public/asset-images/Lap-0001/Lap-0001-001.jpg',
                'description' => 'Tampak depan',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_asset' => 'PC-0001',
                'filename' => 'PC-0001-001.jpg',
                'image_url' => 'storage/app/public/asset-images/PC-0001/PC-0001-001.jpg',
                'description' => 'Serial number',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
