<?php

namespace App\GraphQL\Mutations;

use App\Http\Requests\CreatePartRequest;
use App\Models\Version;
use App\Models\Revision;
use App\Models\AdditionalField;
use App\Models\Group;
use App\Models\Part;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class PartResolver
{
    public function createPart($_, array $args)
    {
        return DB::transaction(function () use ($args) {
            // Kiểm tra dữ liệu đầu vào của input
            $request = new CreatePartRequest();
            $request->merge($args['input']);
            $request->setMethod('POST');

            $validator = Validator::make($request->all(), $request->rules());

            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            $data = $validator->validated();

            // Check unique code
            if (Part::where('code', $data['code'])->exists()) {
                throw new \Exception('This code already exists!');
            }

            $part = Part::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'type_id' => $data['type_id'],
                'description' => $data['description'] ?? null,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $revision = Revision::create([
                'part_id' => $part->id,
                'revision_code' => '1.0',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $version = Version::create([
                'revision_id' => $revision->id,
                'version_code' => 'v1.0',
                'name' => $data['name'],
                'code' => $data['code'],
                'type_id' => $data['type_id'],
                'status' => 'Draft',
                'enable_assembly_groups' => $data['enable_assembly_groups'],
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $revision->update(['latest_version' => $version->id]);

            if ($version->enable_assembly_groups == true) {
                $group = Group::create([
                    'assembler_id' => $part->id,
                    'name' => 'Default Group',
                    'version_id' => $version->id,
                    'is_optional' => false
                ]);
            }

            if (!empty($data['additional_fields'])) {
                foreach ($data['additional_fields'] as $field) {
                    AdditionalField::create([
                        'name' => $field['name'],
                        'value' => $field['value'],
                        'version_id' => $version->id,
                        'data_type' => strtolower($field['data_type'] ?? 'string'),
                        'type_group' => strtolower($field['type_group'] ?? 'custom'),
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

            // Lấy Part
            $part = Part::findOrFail($input['id']);

            // Lấy Revision mới nhất
            $revision = Revision::where('part_id', $part->id)
                ->orderBy('id', 'desc')
                ->first();

            if (!$revision) {
                throw new \Exception('Revision not found for this part.');
            }

            // Lấy Version mới nhất
            $latestVersion = $revision->latestVersion;

            if (!$latestVersion || $latestVersion->status !== 'Draft') {
                throw new \Exception('Only Draft versions can be edited.');
            }

            // Kiểm tra trùng code với part khác
            if (Part::where('code', $input['code'])->where('id', '!=', $part->id)->exists()) {
                throw new \Exception('This code already exists!');
            }

            // Đánh dấu version cũ là Archived
            $latestVersion->update(['status' => 'Archived']);

            // Tính version_code mới
            list($revMajor, $revMinor) = explode('.', $revision->revision_code);
            $versionCount = $revision->versions()->count();
            $versionCode = 'v' . $revMajor . '.' . $versionCount;

            // Tạo version mới
            $version = Version::create([
                'revision_id' => $revision->id,
                'version_code' => $versionCode,
                'name' => $input['name'] ?? $part->name,
                'code' => $input['code'] ?? $part->code,
                'description' => $input['description'] ?? $part->description,
                'type_id' => $input['type_id'] ?? $part->type_id,
                'status' => 'Draft',
                'enable_assembly_groups' => false,
                'based_upon_version_id' => $latestVersion?->id,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Cập nhật revision → latest_version
            $revision->update([
                'latest_version' => $version->id,
                'updated_at' => now(),
            ]);

            // Optional: nếu bạn muốn update Part trực tiếp:
            $part->update([
                'name' => $input['name'] ?? $part->name,
                'code' => $input['code'] ?? $part->code,
                'description' => $input['description'] ?? $part->description,
                'type_id' => $input['type_id'] ?? $part->type_id,
                'updated_at' => now(),
            ]);

            // Xử lý Additional Fields
            if (!empty($input['additional_fields'])) {
                foreach ($input['additional_fields'] as $field) {
                    AdditionalField::create([
                        'name' => $field['name'],
                        'value' => $field['value'],
                        'version_id' => $version->id,
                        'data_type' => strtolower($field['data_type'] ?? 'string'),
                        'type_group' => strtolower($field['type_group'] ?? 'custom'),
                    ]);
                }
            }

            return $version;
        });
    }

    public function resolveAdditionalFields(Version $version, array $args)
    {
        return $version->additionalFields ?? [];
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
                //->where('status', 'Published')
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
