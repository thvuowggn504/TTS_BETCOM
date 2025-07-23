<?php

namespace App\Repositories;

use App\Models\Group;

class GroupRepository
{
    public function findById(int $id): ?Group
    {
        return Group::find($id);
    }

    public function updateGroup(Group $group, array $data): Group
    {
        $group->update($data);
        return $group;
    }
}
