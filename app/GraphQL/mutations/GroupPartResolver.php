<?php

namespace App\GraphQL\Mutations;

use App\Models\GroupPart;

class GroupPartResolver
{
    public function create($_, array $args)
    {
        $input = $args['input'];

        $exists = GroupPart::where('group_id', $input['group_id'])
            ->where('part_id', $input['part_id'])
            ->first();

        if ($exists) {
            throw new \Exception('Part already added to group.');
        }

        return GroupPart::create($input);
    }
}
