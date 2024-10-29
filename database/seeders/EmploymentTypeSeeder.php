<?php

namespace Database\Seeders;

use App\Models\EmploymentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmploymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmploymentType::insert([
            ['category' => 'Regular', 'salary' => 480.00],
            ['category' => 'Trainee', 'salary' => 380.00],
            ['category' => 'Part-time', 'salary' => 50],
        ]);
    }
}
