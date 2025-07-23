<?php

namespace App\GraphQL\Mutations;

use App\Services\GroupService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class GroupResolver
{
    protected GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function updateGroupById($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $this->groupService->editGroup($args['input']);
    }
}
