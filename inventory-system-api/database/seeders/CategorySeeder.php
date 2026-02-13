<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Laptop', 'id_category' => 'Lap'],
            ['category_name' => 'Komputer Desktop', 'id_category' => 'PC'],
            ['category_name' => 'Alat Jaringan', 'id_category' => 'AJ'],
            ['category_name' => 'Perkakas', 'id_category' => 'Per'],
            ['category_name' => 'Monitor', 'id_category' => 'Mon'],
            ['category_name' => 'Proyektor', 'id_category' => 'Pro'],
            ['category_name' => 'Aksesoris', 'id_category' => 'Aks'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
