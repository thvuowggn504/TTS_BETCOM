<?php

namespace App\Services;

use App\Repositories\GroupRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Group;

class GroupService
{
    protected GroupRepository $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    public function editGroup(array $input): Group
    {
        // Validate input
        $validator = Validator::make($input, [
            'id' => 'required|exists:groups,id',
            'name' => 'nullable|string|max:255',
            'type_id' => 'nullable|exists:type,id',
            'is_optional' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $group = $this->groupRepository->findById($input['id']);
        if (!$group) {
            throw new \Exception("Group not found");
        }

        $data = [
            'name' => $input['name'] ?? $group->name,
            'type_id' => $input['type_id'] ?? $group->type_id,
            'is_optional' => $input['is_optional'] ?? $group->is_optional,
        ];

        return $this->groupRepository->updateGroup($group, $data);
    }
}

