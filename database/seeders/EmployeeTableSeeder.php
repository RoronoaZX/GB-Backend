<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::create([
            'employment_type_id' => '1',
            'firstname' => 'John',
            'middlename' => 'Dart',
            'lastname' => 'Doe',
            'birthdate' => '1990-06-15',
            'phone' => '09351212121',
            'address' => 'Street 123123',
            'sex' => 'Male',
            'position' => 'Admin',
        ]);
    }
}
