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
            ['name' => 'standard'],
            ['name' => 'optic set'],
            ['name' => 'led'],
            ['name' => 'driver'],
            ['name' => 'luminaire'],
            ['name' => 'engine'],
        ]);
    }
}
