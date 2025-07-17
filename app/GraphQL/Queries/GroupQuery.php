<?php

namespace App\GraphQL\Queries;

use App\Models\Version;
use App\Models\Part;
use App\Models\Group;
use App\Models\GroupPart;

class GroupQuery
{
    public function getGroupsByVersionId($_, array $args)
    {
        return Group::whereHas('version', function ($query) use ($args) {
            $query->where('version_id', $args['versionId']);
        })->get();
    }
}