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
            // $user = auth()->user();

            // 1. Tạo part mới
            $part = Part::create([
                'name' => $input['name'],
                'code' => $input['code'],
                'type_id' => $input['type_id'],
                'description' => $input['description'] ?? null,
                // 'created_by' => $user->id
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // 2. Tạo revision mặc định 1.0
            $revision = Revision::create([
                'part_id' => $part->id,
                'revision_code' => '1.0',
                // 'created_by' => $user->id
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // 3. Tạo version mặc định 1.0 với status Draft
            $version = Version::create([
                'revision_id' => $revision->id,
                'version_code' => '1.0',
                'name' => $input['name'] . ' - Initial Version',
                'code' => $input['code'],
                'type_id' => $input['type_id'],
                'status' => 'Draft',
                // 'created_by' => $user->id
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // 4. Cập nhật latest_version cho revision
            $revision->update(['latest_version' => $version->id]);

            // 5. Thêm additional fields nếu có
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
}
