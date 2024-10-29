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
        User::create([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => Hash::make('password'),
            'birthdate' => '1990-01-01',
            'address' => '123 Example St, City',
            'sex' => 'Male',
            'status' => 'Current',
            'phone' => '1234567890',
            'role' => 'Admin',
        ]);
    }
}
