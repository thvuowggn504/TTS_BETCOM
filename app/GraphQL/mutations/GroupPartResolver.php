<?php

namespace App\GraphQL\Mutations;

use App\Models\GroupPart;
use App\Services\GroupPartService;

class GroupPartResolver
{
    protected $groupPartService;
    public function __construct(GroupPartService $groupPartService)
    {
        $this->groupPartService = $groupPartService;
    }
    public function create($_, array $args)
    {
        return $this->groupPartService->createGroupPart($args['input']);
    }

    public function deleteGroup($_, array $args)
    {
        return $this->groupPartService->deleteGroupPart($args['id']);
    }
}
