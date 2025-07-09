<?php

namespace App\GraphQL\Queries;

use App\Models\Version;
use App\Models\Part;


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

    public function getAllParts($_, array $args) {
        return Part::with([
            'revisions.versions' => function($q) {
                $q ->where('status','Published')->latest();
            },
            'additionalFields'
        ])->get();
    }
}
