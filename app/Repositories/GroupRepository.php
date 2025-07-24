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

    public function createGroup(array $data): Group
    {
        return Group::create($data);
    }

    public function existsWithNameAndVersion(string $name, ?int $versionId): bool
    {
        return Group::where('name', $name)
            ->where('version_id', $versionId)
            ->exists();
    }
}
