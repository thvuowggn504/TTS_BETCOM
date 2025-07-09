<?php

namespace Database\Seeders;

use App\Models\Part;
use App\Models\Revision;
use App\Models\Version;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('versions')->delete();
        DB::table('revisions')->delete();
        DB::table('parts')->delete();

        // Create parts
        $part1 = Part::create(['id' => 1, 'created_by' => 1]);
        $part2 = Part::create(['id' => 2]);
        $part3 = Part::create(['id' => 3]);

        // Create revisions
        $rev1 = Revision::create([
            'id' => 1,
            'part_id' => $part1->id,
            'revision_code' => 'R1',
            'created_by' => 1,
            'created_at' => Carbon::parse('2025-07-09 14:51:42')
        ]);
        $rev2 = Revision::create([
            'id' => 2,
            'part_id' => $part2->id,
            'revision_code' => '1',
            'created_at' => Carbon::parse('2025-07-09 14:51:40')
        ]);

        $rev3 = Revision::create([
            'id' => 3,
            'part_id' => $part3->id,
            'revision_code' => '1.0',
            'created_at' => Carbon::parse('2025-07-09 14:53:15')
        ]);

        $rev4 = Revision::create([
            'id' => 4,
            'part_id' => $part3->id,
            'revision_code' => '2.0',
            'created_at' => Carbon::parse('2025-07-09 14:53:59')
        ]);

        // Create versions
        Version::create([
            'id' => 1,
            'revision_id' => 1,
            'version_code' => '1.0',
            'name' => 'Sample Part v1.0',
            'code' => 'SP-DSLDR',
            'type_id' => 1,
            'status' => 'Draft',
            'enable_assembly_groups' => true,
        ]);

        Version::create([
            'id' => 2,
            'revision_id' => 3,
            'version_code' => '1.0',
            'name' => 'abc',
            'code' => 'abc',
            'type_id' => 2,
            'status' => 'Draft',
        ]);

        Version::create([
            'id' => 3,
            'revision_id' => 2,
            'version_code' => '1.0',
            'name' => '123',
            'code' => '123',
            'type_id' => 4,
            'status' => 'Draft',
        ]);

        Version::create([
            'id' => 4,
            'revision_id' => 2,
            'version_code' => '1.1',
            'name' => '123',
            'code' => '123456',
            'based_upon_version_id' => 3,
            'type_id' => 3,
            'status' => 'Published',
        ]);

        Version::create([
            'id' => 5,
            'revision_id' => 4,
            'version_code' => '2.0',
            'name' => 'abc',
            'code' => 'abcdefg',
            'type_id' => 2,
            'status' => 'Published',
        ]);

        
    }
}
