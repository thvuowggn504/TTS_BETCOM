<?php

namespace App\Repositories;

use App\Models\AdditionalField;

class AdditionalFieldRepository
{
    public function createMany(array $fields, int $versionId)
    {
        foreach ($fields as $field) {
            AdditionalField::create([
                'name' => $field['name'],
                'value' => $field['value'],
                'version_id' => $versionId,
                'type_id' => $field['type_id'] ?? null,
                'data_type' => strtolower($field['data_type'] ?? 'string'),
                'type_group' => 'custom',
            ]);
        }
    }

    public function updateOrCreateMany(array $fields, int $versionId)
    {
        foreach ($fields as $field) {
            AdditionalField::updateOrCreate(
                ['version_id' => $versionId, 'name' => $field['name']],
                [
                    'value' => $field['value'],
                    'data_type' => strtolower($field['data_type'] ?? 'string'),
                ]
            );
        }
    }
}
