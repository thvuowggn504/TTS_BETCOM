<?php

// database/seeders/PartSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Part;
use App\Models\Revision;
use App\Models\Version;
use Illuminate\Support\Str;

class PartSeeder extends Seeder
{
    public function run(): void
    {
        $part = Part::create(['created_by' => 1]);

        $revision = Revision::create([
            'part_id' => $part->id,
            'revision_code' => 'R1',
            'created_by' => 1
        ]);

        $version = Version::create([
            'revision_id' => $revision->id,
            'version_code' => '1.0',
            'name' => 'Sample Part v1.0',
            'code' => 'SP-' . strtoupper(Str::random(5)),
            'description' => 'Sample part description',
            'status' => 'Published',
            'enable_assembly_groups' => true,
            'created_by' => 1,
        ]);

        $revision->update(['latest_version' => $version->id]);
    }
}

