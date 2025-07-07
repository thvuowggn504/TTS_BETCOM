<?php

// database/seeders/CodebuilderRuleSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CodebuilderRule;
use App\Models\Version;

class CodebuilderRuleSeeder extends Seeder
{
    public function run(): void
    {
        $version = Version::first();

        CodebuilderRule::create([
            'version_id' => $version->id,
            'rule' => json_encode([
                'prefix' => 'SP',
                'fields' => ['type', 'color', 'watt']
            ])
        ]);
    }
}

