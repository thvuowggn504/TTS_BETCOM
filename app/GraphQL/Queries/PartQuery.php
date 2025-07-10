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

    public function getAllParts($_, array $args)
    {
        return Part::whereHas('revisions.versions', function ($query) {
            $query->whereIn('status', ['Published', 'Draft']);
        })
            ->with([
                'revisions' => function ($revisionQuery) {
                    $revisionQuery->with([
                        'versions' => function ($versionQuery) {
                            // Ưu tiên Published, nếu không thì lấy Draft
                            $versionQuery->orderByRaw("
                        CASE 
                            WHEN status = 'Published' THEN 0
                            WHEN status = 'Draft' THEN 1
                            ELSE 2
                        END
                    ")->limit(1);
                        }
                    ]);
                },
                'additionalFields'
            ])
            ->get();
    }
}
