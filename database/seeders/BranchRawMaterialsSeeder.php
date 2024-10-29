<?php

namespace Database\Seeders;

use App\Models\BranchRawMaterialsReport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchRawMaterialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branchRawmaterials = [
            [
                'branch_id' => 2,
                'ingredients_id' => 1,
                'total_quantity' => 5000,
            ],
            [
                'branch_id' => 2,
                'ingredients_id' => 2,
                'total_quantity' => 3000,
            ],
            [
                'branch_id' => 2,
                'ingredients_id' => 3,
                'total_quantity' => 7000,
            ],
            [
                'branch_id' => 2,
                'ingredients_id' => 4,
                'total_quantity' => 5000,
            ],
            [
                'branch_id' => 2,
                'ingredients_id' => 5,
                'total_quantity' => 3000,
            ],
            [
                'branch_id' => 2,
                'ingredients_id' => 6,
                'total_quantity' => 7000,
            ],
            [
                'branch_id' => 2,
                'ingredients_id' => 1,
                'total_quantity' => 5000,
            ],
            [
                'branch_id' => 2,
                'ingredients_id' => 2,
                'total_quantity' => 3000,
            ],
            [
                'branch_id' => 2,
                'ingredients_id' => 3,
                'total_quantity' => 7000,
            ],
        ];

        foreach ($branchRawmaterials as $ingredient) {

            BranchRawMaterialsReport::create( $ingredient);
        }
    }
}
