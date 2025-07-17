<?php

namespace App\GraphQL\Queries;

use App\Models\Version;
use App\Models\Part;
use App\Models\Group;
use App\Models\GroupPart;

class GroupQuery
{
    public function getGroupsByPartId($_, array $args)
    {
        return Group::whereHas('assembler', function ($query) use ($args) {
            $query->where('assembler_id', $args['partId']);
        })->get();
    }
}