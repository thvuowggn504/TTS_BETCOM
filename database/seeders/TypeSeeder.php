<?php

// database/seeders/TypeSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('type')->insert([
            ['name' => 'Standard'],
            ['name' => 'Optic set'],
            ['name' => 'Led'],
            ['name' => 'Driver'],
            ['name' => 'Luminaire'],
            ['name' => 'Engine'],
        ]);
    }
}
