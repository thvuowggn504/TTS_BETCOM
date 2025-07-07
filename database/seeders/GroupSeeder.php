<?php

// database/seeders/GroupSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\GroupPart;
use App\Models\Part;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        // Giả sử PartSeeder đã tạo 1 part đầu tiên là Assembler
        $assembler = Part::first();

        // Thêm 2 part phụ để gắn vào group
        $sub1 = Part::create(['created_by' => 1]);
        $sub2 = Part::create(['created_by' => 1]);

        $group = Group::create([
            'assembler_id' => $assembler->id,
            'name' => 'Main Group',
            'version_id' => $assembler->revisions->first()->latest_version,
            'is_optional' => false,
        ]);

        GroupPart::create([
            'group_id' => $group->id,
            'part_id' => $sub1->id,
            'quantity' => 2,
        ]);

        GroupPart::create([
            'group_id' => $group->id,
            'part_id' => $sub2->id,
            'quantity' => 1,
        ]);
    }
}

