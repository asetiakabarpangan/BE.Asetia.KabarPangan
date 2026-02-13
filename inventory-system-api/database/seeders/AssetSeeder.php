<?php

namespace Database\Seeders;

use App\Models\Asset;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Asset::create([
            'id_asset' => "Lap-0001",
            'id_category' => 'Lap',
            'asset_name' => 'Laptop Lenovo ThinkPad X280',
            'brand' => 'Lenovo',
            'specification' => [
                'Processor' => [
                    'name' => 'Intel Core i5-8250U',
                    'value' => '3.40 GHz',
                    'condition' => 'Baik'
                ],
                'RAM' => [
                    'name' => 'V-Gen Sodimm Platinum DDR4 PC19200',
                    'value' => '16GB',
                    'condition' => 'Baik'
                ],
                'Storage' => [
                    'name' => 'SSD KINGSTON UV300 SATA3',
                    'value' => '256GB',
                    'condition' => 'Baik'
                ],
                'Graphics' => [
                    'name' => 'Intel UHD Graphics 620',
                    'value' => 'Integrated GPU',
                    'condition' => 'Baik'
                ],
                'Display' => [
                    'name' => 'IPS LED 14 Inch',
                    'value' => '1920x1080 (Full HD)',
                    'condition' => 'Baik'
                ],
                'Battery' => [
                    'name' => 'Lithium-ion Battery',
                    'value' => '42Wh',
                    'condition' => 'Baik'
                ],
                'WiFi' => [
                    'name' => 'Intel Dual Band Wireless-AC 8265',
                    'value' => '802.11 a/b/g/n/ac',
                    'condition' => 'Baik'
                ],
                'Bluetooth' => [
                    'name' => 'Bluetooth Module',
                    'value' => 'Bluetooth 4.2',
                    'condition' => 'Baik'
                ],
                'Ports' => [
                    'name' => 'USB / HDMI / Audio Jack',
                    'value' => '2x USB 3.0, 1x USB 2.0, 1x HDMI, Audio Combo Jack',
                    'condition' => 'Baik'
                ],
                'Operating System' => [
                    'name' => 'Windows 10 Pro',
                    'value' => '64-bit',
                    'condition' => 'Baik'
                ],
                'Keyboard' => [
                    'name' => 'Standard Laptop Keyboard',
                    'value' => 'Backlit: Tidak',
                    'condition' => 'Baik'
                ],
                'Touchpad' => [
                    'name' => 'Precision Touchpad',
                    'value' => 'Multi Gesture Support',
                    'condition' => 'Baik'
                ],
            ],
            'id_location' => 2,
            'condition' => 'Baik',
            'acquisition_date' => '2021-05-14',
            'availability_status' => 'Dipinjam',
            'information' => 'Unit digunakan untuk staf administrasi.',
        ]);

        Asset::create([
            'id_asset' => "PC-0001",
            'id_category' => 'PC',
            'asset_name' => 'PC Rakitan i5-10400F',
            'brand' => 'Rakitan',
            'specification' => [
                'Processor' => [
                    'name' => 'Intel Core i5-10400F',
                    'value' => '2.9 GHz (4.3 GHz Turbo)',
                    'condition' => 'Baik'
                ],
                'RAM' => [
                    'name' => 'DDR4 Memory',
                    'value' => '16GB (3200MHz)',
                    'condition' => 'Baik'
                ],
                'Graphics' => [
                    'name' => 'NVIDIA GeForce GTX 1650',
                    'value' => '4GB GDDR5',
                    'condition' => 'Baik'
                ],
                'Motherboard' => [
                    'name' => 'H510 / B460 Series Motherboard',
                    'value' => 'Socket LGA1200, DDR4, PCIe x16',
                    'condition' => 'Baik'
                ],
                'Storage' => [
                    'name' => 'SSD SATA / NVMe',
                    'value' => '512GB',
                    'condition' => 'Baik'
                ],
                'Power Supply' => [
                    'name' => 'ATX Power Supply',
                    'value' => '450W',
                    'condition' => 'Baik'
                ],
                'Casing' => [
                    'name' => 'ATX PC Case',
                    'value' => 'Mid Tower',
                    'condition' => 'Baik'
                ],
                'Cooling' => [
                    'name' => 'CPU Cooler Fan',
                    'value' => '120mm Air Cooler',
                    'condition' => 'Baik'
                ],
                'Networking' => [
                    'name' => 'Gigabit LAN',
                    'value' => '10/100/1000 Mbps',
                    'condition' => 'Baik'
                ],
                'Operating System' => [
                    'name' => 'Windows 10 Pro',
                    'value' => '64-bit',
                    'condition' => 'Baik'
                ],
                'Keyboard' => [
                    'name' => 'Standard Keyboard',
                    'value' => 'Wired USB',
                    'condition' => 'Baik'
                ],
                'Mouse' => [
                    'name' => 'Standard Mouse',
                    'value' => 'Wired USB',
                    'condition' => 'Baik'
                ],
            ],
            'id_location' => 2,
            'condition' => 'Baik',
            'acquisition_date' => '2020-09-10',
            'availability_status' => 'Tersedia',
            'information' => 'Digunakan untuk keperluan desain grafis.',
        ]);

        Asset::create([
            'id_asset' => "PC-0002",
            'id_category' => 'PC',
            'asset_name' => 'PC Rakitan i5-10400F',
            'brand' => 'Rakitan',
            'specification' => [
                'Processor' => [
                    'name' => 'Intel Core i5-10400F',
                    'value' => '2.9 GHz (4.3 GHz Turbo)',
                    'condition' => 'Baik'
                ],
                'RAM' => [
                    'name' => 'DDR4 Memory',
                    'value' => '16GB (3200MHz)',
                    'condition' => 'Baik'
                ],
                'Graphics' => [
                    'name' => 'NVIDIA GeForce GTX 1650',
                    'value' => '4GB GDDR5',
                    'condition' => 'Baik'
                ],
                'Motherboard' => [
                    'name' => 'H510 / B460 Series Motherboard',
                    'value' => 'Socket LGA1200, DDR4, PCIe x16',
                    'condition' => 'Baik'
                ],
                'Storage' => [
                    'name' => 'SSD SATA / NVMe',
                    'value' => '512GB',
                    'condition' => 'Baik'
                ],
                'Power Supply' => [
                    'name' => 'ATX Power Supply',
                    'value' => '450W',
                    'condition' => 'Baik'
                ],
                'Casing' => [
                    'name' => 'ATX PC Case',
                    'value' => 'Mid Tower',
                    'condition' => 'Baik'
                ],
                'Cooling' => [
                    'name' => 'CPU Cooler Fan',
                    'value' => '120mm Air Cooler',
                    'condition' => 'Baik'
                ],
                'Networking' => [
                    'name' => 'Gigabit LAN',
                    'value' => '10/100/1000 Mbps',
                    'condition' => 'Baik'
                ],
                'Operating System' => [
                    'name' => 'Windows 10 Pro',
                    'value' => '64-bit',
                    'condition' => 'Baik'
                ],
                'Keyboard' => [
                    'name' => 'Standard Keyboard',
                    'value' => 'Wired USB',
                    'condition' => 'Baik'
                ],
                'Mouse' => [
                    'name' => 'Standard Mouse',
                    'value' => 'Wired USB',
                    'condition' => 'Baik'
                ],
            ],
            'id_location' => 2,
            'condition' => 'Baik',
            'acquisition_date' => '2020-09-10',
            'availability_status' => 'Tersedia',
            'information' => 'Digunakan untuk keperluan desain grafis.',
        ]);

        Asset::create([
            'id_asset' => "PC-0003",
            'id_category' => 'PC',
            'asset_name' => 'PC Rakitan i5-10400F',
            'brand' => 'Rakitan',
            'specification' => [
                'Processor' => [
                    'name' => 'Intel Core i5-10400F',
                    'value' => '2.9 GHz (4.3 GHz Turbo)',
                    'condition' => 'Baik'
                ],
                'RAM' => [
                    'name' => 'DDR4 Memory',
                    'value' => '16GB (3200MHz)',
                    'condition' => 'Baik'
                ],
                'Graphics' => [
                    'name' => 'NVIDIA GeForce GTX 1650',
                    'value' => '4GB GDDR5',
                    'condition' => 'Baik'
                ],
                'Motherboard' => [
                    'name' => 'H510 / B460 Series Motherboard',
                    'value' => 'Socket LGA1200, DDR4, PCIe x16',
                    'condition' => 'Baik'
                ],
                'Storage' => [
                    'name' => 'SSD SATA / NVMe',
                    'value' => '512GB',
                    'condition' => 'Baik'
                ],
                'Power Supply' => [
                    'name' => 'ATX Power Supply',
                    'value' => '450W',
                    'condition' => 'Baik'
                ],
                'Casing' => [
                    'name' => 'ATX PC Case',
                    'value' => 'Mid Tower',
                    'condition' => 'Baik'
                ],
                'Cooling' => [
                    'name' => 'CPU Cooler Fan',
                    'value' => '120mm Air Cooler',
                    'condition' => 'Baik'
                ],
                'Networking' => [
                    'name' => 'Gigabit LAN',
                    'value' => '10/100/1000 Mbps',
                    'condition' => 'Baik'
                ],
                'Operating System' => [
                    'name' => 'Windows 10 Pro',
                    'value' => '64-bit',
                    'condition' => 'Baik'
                ],
                'Keyboard' => [
                    'name' => 'Standard Keyboard',
                    'value' => 'Wired USB',
                    'condition' => 'Baik'
                ],
                'Mouse' => [
                    'name' => 'Standard Mouse',
                    'value' => 'Wired USB',
                    'condition' => 'Baik'
                ],
            ],
            'id_location' => 2,
            'condition' => 'Baik',
            'acquisition_date' => '2020-09-10',
            'availability_status' => 'Tersedia',
            'information' => 'Digunakan untuk keperluan desain grafis.',
        ]);
    }
}
