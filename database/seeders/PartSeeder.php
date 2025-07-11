<?php

// database/seeders/PartSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Part;
use App\Models\Revision;
use App\Models\Version;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartSeeder extends Seeder
{
    public function run(): void
    {

        // Tạo user giả nếu chưa có
        $userId = DB::table('users')->insertGetId([
            'name' => 'Seeder User',
            'email' => 'seeder@example.com',
            'password_hash' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $versionCounter = 1;

        for ($i = 1; $i <= 5; $i++) {

            $type_id = rand(1, 6);
            // Tạo Part
            $partId = DB::table('parts')->insertGetId([
                'name' => "Part $i",
                'code' => "PART-$i",
                'description' => "Description for Part $i",
                'type_id' => $type_id,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Tạo 1–2 Revisions cho mỗi Part
            $revisionCount = rand(1, 3);
            $revisionCounter = 1;

            for ($r = 1; $r <= $revisionCount; $r++) {
                $revisionCode = $revisionCounter . '.0';
                $revisionId = DB::table('revisions')->insertGetId([
                    'part_id' => $partId,
                    'revision_code' => $revisionCode,
                    'created_by' => $userId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                // Tạo 1–4 Versions cho mỗi Revision
                $versionCount = rand(1, 4);
                $hasStatus = false;
                $latestVersionId = null;

                for ($v = 1; $v <= $versionCount; $v++) {
                    $status = 'Archived';
                    // Đảm bảo chỉ có 1 Draft hoặc Published
                    if (!$hasStatus || $v === $versionCount) {
                        $status = rand(0, 1) ? 'Draft' : 'Published';
                        $hasStatus = true;
                    }

                    $versionId = DB::table('versions')->insertGetId([
                        'revision_id' => $revisionId,
                        'version_code' => "1.$v",
                        'name' => "Version $versionCounter",
                        'code' => "V-$versionCounter",
                        'based_upon_version_id' => $latestVersionId,
                        'description' => "Description for version $versionCounter",
                        'type_id' => $type_id,
                        'status' => $status,
                        'enable_assembly_groups' => rand(0, 1),
                        'created_by' => $userId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $versionCounter++;
                    $latestVersionId = $versionId;
                }
                $revisionCounter++;

                // Cập nhật latest_version cho revision
                DB::table('revisions')->where('id', $revisionId)->update([
                    'latest_version' => $latestVersionId
                ]);
            }
        }
    }
}
