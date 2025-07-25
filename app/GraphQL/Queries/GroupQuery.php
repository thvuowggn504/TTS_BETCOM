<?php

namespace App\GraphQL\Queries;

use App\Models\Version;
use App\Models\Part;
use App\Models\Group;
use App\Models\GroupPart;
use App\Services\GroupService;

class GroupQuery
{
    protected $groupService;
    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }
    public function getGroupsByVersionId($_, array $args)
    {
        return Group::whereHas('version', function ($query) use ($args) {
            $query->where('version_id', $args['versionId']);
        })->get();
    }

    public function getAdditionalFieldsFromGroup($_, array $args) {
        return $this->groupService->getAdditionalFieldsFromGroup($args['groupId']);
    }
}
