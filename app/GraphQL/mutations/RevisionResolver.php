<?php

namespace App\GraphQL\Mutations;

use App\Models\Revision;
use App\Models\Version;
use Illuminate\Support\Facades\DB;

class RevisionResolver
{
    public function createFromVersion($_, array $args)
    {
        $input = $args['input'];

        return DB::transaction(function () use ($input) {
            $version = Version::findOrFail($input['version_id']);

            $revision = Revision::create([
                'part_id' => $version->revision->part_id,
                'revision_code' => $input['revision_code'],
                'created_by' => $input['created_by'] ?? null,
            ]);

            $newVersion = Version::create([
                'revision_id' => $revision->id,
                'version_code' => '1.0',
                'name' => $version->name,
                'code' => $version->code,
                'based_upon_version_id' => $version->id,
                'description' => $version->description,
                'type_id' => $version->type_id,
                'status' => 'Draft',
                'enable_assembly_groups' => $version->enable_assembly_groups,
                'created_by' => $input['created_by'] ?? null,
            ]);

            $revision->update(['latest_version' => $newVersion->id]);

            return $revision->load('versions');
        });
    }
}
