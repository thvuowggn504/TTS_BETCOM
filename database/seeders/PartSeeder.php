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
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $versionCounter = 1;
        $now = now(); // Carbon instance

        for ($i = 1; $i <= 6; $i++) {

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

                // Gán thời gian rồi tăng 1 giây
                $revisionTime = $now->copy()->addSeconds($r - 1);

                $revisionId = DB::table('revisions')->insertGetId([
                    'part_id' => $partId,
                    'revision_code' => $revisionCode,
                    'created_by' => $userId,
                    'created_at' => $revisionTime,
                    'updated_at' => $revisionTime
                ]);

                // Tạo 1–4 Versions cho mỗi Revision
                $versionCount = rand(2, 4);
                $latestVersionId = null;

                for ($v = 0; $v <= $versionCount; $v++) {
                    $status = 'Archived';
                    if (1 == $versionCount) {
                        $status = 'Draft';
                    }
                    if ($v == $versionCount && $v > 1) {
                        $status = 'Draft';
                        $previousVersion = Version::findOrFail($latestVersionId);
                        $previousVersion->update([
                            'status' => 'Published'
                        ]);
                    }

                    // Tăng thời gian mỗi lần thêm version
                    $versionTime = $revisionTime->copy()->addSeconds($v);
                    $enableAssemblyGroups = $type_id == 5? true : false;
                    // Nếu là version cuối cùng, bật nhóm lắp ráp và đặt type_id là luminaire
                    if ($v == $versionCount) {
                        $enableAssemblyGroups = true; // Luôn bật nhóm lắp ráp cho version cuối cùng
                        $type_id = 5; // Đặt type_id là luminaire 
                    }
                    $versionId = DB::table('versions')->insertGetId([
                        'revision_id' => $revisionId,
                        'version_code' => "1.$v",
                        'name' => "Part $i",
                        'code' => "PART-$i",
                        'based_upon_version_id' => $latestVersionId,
                        'description' => "Description for Part $i",
                        'type_id' => $type_id,
                        'status' => $status,
                        'enable_assembly_groups' => $enableAssemblyGroups,
                        'created_by' => $userId,
                        'created_at' => $versionTime,
                        'updated_at' => $versionTime
                    ]);

                    $versionCounter++;
                    $latestVersionId = $versionId;

                    // Nếu type là luminaire, tạo 1 group
                    if ($enableAssemblyGroups) {
                        DB::table('groups')->insert([
                            'name' => "Default Group",
                            'assembler_id' => $partId,
                            'version_id' => $versionId,
                        ]);
                        DB::table('codebuilder')->insert([
                            'name' => "Seeder Code Pattern",
                            'version_id' => $versionId,
                            'is_default' => true,
                        ]);
                    }
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
