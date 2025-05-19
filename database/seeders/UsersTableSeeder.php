<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            // 'name' => 'Cake Mak',
            // 'employee_id' => '1',
            // 'email' => 'johndoe@example.com',
            // 'password' => Hash::make('password'),
            // 'birthdate' => '1990-01-01',
            // 'address' => '123 Example St, City',
            // 'sex' => 'Male',
            // 'status' => 'Current',
            // 'phone' => '1234567890',
            // 'role' => 'Super Admin',
            // 'name' => 'Cake Mak',
            'employee_id' => '157',
            'email' => 'arguerro@gmail.com',
            'password' => Hash::make('@Arguerro123'),
            // 'birthdate' => '1990-01-01',
            // 'address' => '123 Example St, City',
            // 'sex' => 'Male',
            // 'status' => 'Current',
            // 'phone' => '1234567890',
            'role' => 'Super Admin',
        ]);
    }
}
