<?php

namespace Database\Seeders;

use App\Models\BranchEmployee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BranchEmployee::create([
        'branch_id' => 1,
        'user_id' => 1,
        'time_shift' => '08:00:00',
        ]);
    }
}
