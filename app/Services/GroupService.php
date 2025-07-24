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
        // Ép kiểu an toàn trước khi validate
        $input['id'] = (int) $input['id'];

        // Nếu truyền vào là 'type' (theo name) → convert sang type_id
        if (isset($input['type'])) {
            $type = \App\Models\Type::where('name', $input['type'])->first();

            if (!$type) {
                throw ValidationException::withMessages([
                    'type' => 'Invalid type name provided.',
                ]);
            }

            $input['type_id'] = $type->id;
            unset($input['type']); // Xoá để tránh lỗi validate vì không có field 'type'
        }

        // Tiếp tục ép kiểu type_id nếu tồn tại
        if (isset($input['type_id'])) {
            $input['type_id'] = (int) $input['type_id'];
        }

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

    public function create(array $input)
    {
        if ($this->groupRepository->existsWithNameAndVersion($input['name'], $input['version_id'] ?? null)) {
            throw ValidationException::withMessages([
                'name' => 'The group name must be unique within the same version.',
            ]);
        }

        return $this->groupRepository->createGroup($input);
    }
}
