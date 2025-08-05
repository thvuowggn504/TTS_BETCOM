<?php

namespace App\GraphQL\Mutations;

use App\Services\GroupService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Facades\Log;
use App\Models\Group;

class GroupResolver
{
    protected GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function updateGroupById($_, array $args)
    {
        $input = $args['input'];
        $group = Group::findOrFail($input['id']);
        $toCamalCase = function (string $str): string {
            $str = preg_replace('/[^a-zA-Z0-9 ]/', '', $str); // Xóa ký tự đặc biệt
            $words = explode(' ', strtolower($str));
            $camel = array_shift($words);
            foreach ($words as $word) {
                $camel .= ucfirst($word);
            }
            return $camel;
        };

        $nameId = $toCamalCase($group->name);

        $group->update([
            'name' => $input['name'] ?? $group->name,
            'name_id' => $nameId,
            'type_id' => $input['type_id'] ?? $group->type_id,
            'version_id' => $input['version_id'] ?? $group->version_id,
            'assembler_id' => $input['assembler_id'] ?? $group->assembler_id,
            'is_optional' => $input['is_optional'] ?? $group->is_optional,
        ]);

        return $group;
    }

    public function createGroup($_, array $args)
    {
        return $this->groupService->create($args['input']);
    }
}
