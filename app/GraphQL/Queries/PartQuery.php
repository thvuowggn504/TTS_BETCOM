<?php

namespace App\GraphQL\Queries;

use App\Models\Version;

class PartQuery
{
    public function getVersion($_, array $args)
    {
        return Version::whereHas('revision', function ($q) use ($args) {
            $q->where('part_id', $args['partId'])
              ->where('id', $args['revisionId']);
        })
        ->where('version_code', $args['versionCode'])
        ->first();
    }
}
