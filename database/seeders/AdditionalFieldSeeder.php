<?php

// database/seeders/AdditionalFieldSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Type;
use App\Models\AdditionalField;

class AdditionalFieldSeeder extends Seeder
{
    public function run(): void
    {
        $type = Type::where('name', 'led')->first();

        AdditionalField::create([
            'name' => 'Colour Temperature (K)',
            'value' => '3000K',
            'type_id' => $type->id,
            'data_type' => 'string'
        ]);

        AdditionalField::create([
            'name' => 'LED Part No',
            'value' => 'LED-XYZ123',
            'type_id' => $type->id,
            'data_type' => 'string'
        ]);
    }
}
