<?php

namespace App\Repositories;

use App\Models\Version;
use App\Models\Revision;
use App\Models\AdditionalField;
use App\Models\Part;
use App\Models\Group;
use Illuminate\Support\Facades\DB;

class PartRepository
{
    public function createPart(array $input)
    {
        return DB::transaction(function () use ($input) {
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

            if (!empty($input['enable_assembly_groups'])) {
                Group::create([
                    'version_id' => $version->id,
                    'name' => 'Default Group'
                ]);
            }

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

    public function editPart(array $input)
    {
        return DB::transaction(function () use ($input) {
            $part = Part::findOrFail($input['id']);

            $revision = Revision::where('part_id', $part->id)
                ->orderByDesc('id')
                ->first();

            if (!$revision) {
                throw new \Exception("Không tìm thấy revision cho part này.");
            }

            $draftVersion = $revision->versions()
                ->where('status', 'Draft')
                ->orderByDesc('id')
                ->first();

            if (!$draftVersion) {
                throw new \Exception("Không tồn tại version ở trạng thái 'Draft'. Vui lòng tạo bản nháp trước khi chỉnh sửa.");
            }

            $versionCount = $revision->versions()->count();

            $draftVersion->update([
                'status' => 'Archived',
                'updated_at' => now(),
            ]);

            $major = intval(explode('.', $revision->revision_code)[0]);
            $versionCode = 'v' . $major . '.' . $versionCount;

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

            $revision->update(['latest_version' => $version->id]);

            if (!empty($input['enable_assembly_groups'])) {
                Group::create([
                    'version_id' => $version->id,
                    'name' => 'Default Group'
                ]);
            }

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

    public function resolveAdditionalFields(Version $version)
    {
        $revision = $version->revision;
        if (!$revision) return [];

        $part = $revision->part;
        if (!$part) return [];

        return $part->additionalFields;
    }

    public function updateVersionStatus(int $versionId)
    {
        return DB::transaction(function () use ($versionId) {
            $userId = 2;

            $version = Version::findOrFail($versionId);
            $version->update([
                'status' => "Published",
                'updated_at' => now(),
                'created_by' => $userId,
            ]);

            Version::where('revision_id', $version->revision_id)
                ->where('id', '!=', $version->id)
                ->update([
                    'status' => 'Archived',
                    'updated_at' => now(),
                ]);

            $revision = Revision::findOrFail($version->revision_id);
            $revision->update([ 
                'latest_version' => $version->id,
                'updated_at' => now(),
                'created_by' => $userId,
            ]);

            Part::where('id', $revision->part_id)->update([
                'updated_at' => now(),
                'created_by' => $userId,
            ]);

            return $version->fresh();
        });
    }
}