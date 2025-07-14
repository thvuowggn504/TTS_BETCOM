<?php

namespace App\GraphQL\Mutations;

use App\Models\Version;
use App\Models\Revision;
use App\Models\AdditionalField;
use App\Models\Part;
use Illuminate\Support\Facades\DB;

class PartResolver
{
    public function createPart($_, array $args)
    {
        return DB::transaction(function () use ($args) {
            $input = $args['input'];

            $part = Part::create([
                'name' => $input['name'],
                'code' => $input['code'],
                'type_id' => $input['type_id'],
                'description' => $input['description'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1
            ]);

            $revision = Revision::create([
                'part_id' => $part->id,
                'revision_code' => '1.0',
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1
            ]);

            $version = Version::create([
                'revision_id' => $revision->id,
                'version_code' => 'v1.0',
                'name' => $input['name'],
                'code' => $input['code'],
                'type_id' => $input['type_id'],
                'status' => 'Draft',
                'enable_assembly_groups' => $input['enable_assembly_groups'],
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1
            ]);

            $revision->update(['latest_version' => $version->id]);

            if (!empty($input['additional_fields'])) {
                foreach ($input['additional_fields'] as $field) {
                    AdditionalField::create([
                        'name' => $field['name'],
                        'value' => $field['value'],
                        'part_id' => $part->id,
                        'data_type' => strtolower($field['data_type'] ?? 'string')
                    ]);
                }
            }

            return $part;
        });
    }

    public function editPart($_, array $args)
    {
        return DB::transaction(function () use ($args) {
            $input = $args['input'];
            $part = Part::findOrFail($input['id']);

            // Tìm revision đầu tiên
            $revision = Revision::where('part_id', $part->id)
                ->orderBy('id', 'asc')
                ->first();

            if (!$revision) {
                throw new \Exception("Không tìm thấy revision cho part này.");
            }

            // Tìm version đang là Draft
            $draftVersion = $revision->versions()
                ->where('status', 'Draft')
                ->orderByDesc('id')
                ->first();

            if (!$draftVersion) {
                throw new \Exception("Không tồn tại version ở trạng thái 'Draft'. Vui lòng tạo bản nháp trước khi chỉnh sửa.");
            }

            // Đếm version trước khi update draft
            $versionCount = $revision->versions()->count();

            // Archive version Draft cũ
            $draftVersion->update([
                'status' => 'Archived',
                'updated_at' => now(),
            ]);

            // Tạo version_code mới
            $major = intval(explode('.', $revision->revision_code)[0]);
            $versionCode = 'v' . $major . '.' . $versionCount;

            // Tạo version mới ở trạng thái Draft
            $version = Version::create([
                'revision_id' => $revision->id,
                'version_code' => $versionCode,
                'name' => $input['name'] ?? $part->name,
                'code' => $input['code'] ?? $part->code,
                'description' => $input['description'] ?? $part->description,
                'type_id' => $input['type_id'] ?? $part->type_id,
                'status' => 'Draft',
                'enable_assembly_groups' => $input['enable_assembly_groups'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Cập nhật latest_version cho revision
            $revision->update(['latest_version' => $version->id]);

            // Cập nhật các trường bổ sung nếu có
            if (!empty($input['additional_fields'])) {
                foreach ($input['additional_fields'] as $field) {
                    AdditionalField::updateOrCreate(
                        ['part_id' => $part->id, 'name' => $field['name']],
                        [
                            'value' => $field['value'],
                            'data_type' => strtolower($field['data_type'] ?? 'string')
                        ]
                    );
                }
            }

            return $version;
        });
    }

    public function resolveAdditionalFields(Version $version, array $args)
    {
        $revision = $version->revision;
        if (!$revision) return [];

        $part = $revision->part;
        if (!$part) return [];

        return $part->additionalFields;
    }

    public function updateVersionStatus($_, array $args)
    {
        return DB::transaction(function () use ($args) {
            $input = $args['id'];
            $userId = 2; // Lấy user hiện tại hoặc mặc định là 2

            // 1. Cập nhật version hiện tại thành Published
            $version = Version::findOrFail($input);
            $version->update([
                'status' => "Published",
                'updated_at' => now(),
                'created_by' => $userId,
            ]);

            // 2. Cập nhật tất cả version khác trong revision thành Archived
            Version::where('revision_id', $version->revision_id)
                ->where('id', '!=', $version->id)
                ->update([
                    'status' => 'Archived',
                    'updated_at' => now(),
                ]);

            // 3. Cập nhật revision
            $revision = Revision::findOrFail($version->revision_id);
            $revision->update([
                'latest_version' => $version->id,
                'updated_at' => now(),
                'created_by' => $userId,
            ]);

            // 4. Cập nhật part liên quan
            Part::where('id', $revision->part_id)->update([
                'updated_at' => now(),
                'created_by' => $userId,
            ]);

            return $version->fresh();
        });
    }
}
