<?php

namespace Database\Seeders;

use App\Helpers\IdGenerator;
use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Location::create([
            'id_location' => (IdGenerator::generateLocationId('Gedung A')),
            'location_name' => 'Ruang Administrasi',
            'building' => 'Gedung A',
            'id_person_in_charge' => 1,
        ]);

        Location::create([
            'id_location' => 2,
            'location_name' => 'Ruang Server',
            'building' => 'Gedung B',
            'id_person_in_charge' => 1,
        ]);
    }
}
