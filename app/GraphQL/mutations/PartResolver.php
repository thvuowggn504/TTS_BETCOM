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
            ]);

            $revision = Revision::create([
                'part_id' => $part->id,
                'revision_code' => '1.0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $version = Version::create([
                'revision_id' => $revision->id,
                'version_code' => '1.0',
                'name' => $input['name'],
                'code' => $input['code'],
                'type_id' => $input['type_id'],
                'status' => 'Draft',
                'created_at' => now(),
                'updated_at' => now(),
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

            $part->update([
                'name' => $input['name'] ?? $part->name,
                'code' => $input['code'] ?? $part->code,
                'description' => $input['description'] ?? $part->description,
                'type_id' => $input['type_id'] ?? $part->type_id,
                'updated_at' => now(),
            ]);

            $revision = Revision::where('part_id', $part->id)
                ->orderBy('id', 'asc')
                ->first();

            if (!$revision) {
                $revision = Revision::create([
                    'part_id' => $part->id,
                    'revision_code' => 'R1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $versionCount = $revision->versions()->count();
            $revisionNumber = intval(str_replace('R', '', $revision->revision_code));
            $versionCode = 'v' . $revisionNumber . '.' . $versionCount;

            $version = Version::create([
                'revision_id' => $revision->id,
                'version_code' => $versionCode,
                'name' => $part->name,
                'code' => $part->code,
                'description' => $part->description,
                'type_id' => $part->type_id,
                'status' => 'Draft',
                'enable_assembly_groups' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $revision->update(['latest_version' => $version->id]);

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
}
