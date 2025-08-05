<?php

namespace App\Services;

use App\Repositories\GroupRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Repositories\GroupPartRepository;
use App\Repositories\VersionRepository;
use App\Repositories\AdditionalFieldRepository;
use App\Models\Group;

class GroupService
{
    protected GroupRepository $groupRepository;
    protected $groupPartRepository;
    protected $additionalFieldRepository;
    protected $versionRepository;

    public function __construct(
        GroupRepository $groupRepository,
        GroupPartRepository $groupPartRepository,
        VersionRepository $versionRepository,
        AdditionalFieldRepository $additionalFieldRepository
    ) {
        $this->groupRepository = $groupRepository;
        $this->groupPartRepository = $groupPartRepository;
        $this->versionRepository = $versionRepository;
        $this->additionalFieldRepository = $additionalFieldRepository;
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

        $toCamalCase = function (string $str): string {
            $str = preg_replace('/[^a-zA-Z0-9 ]/', '', $str); // Xóa ký tự đặc biệt
            $words = explode(' ', strtolower($str));
            $camel = array_shift($words);
            foreach ($words as $word) {
                $camel .= ucfirst($word);
            }
            return $camel;
        };

        $nameId = $toCamalCase($input['name']);
        return $this->groupRepository->createGroup([
            'name' => $input['name'],
            'name_id' => $nameId,
            'type_id' => $input['type_id'],
            'version_id' => $input['version_id'],
            'is_optional' => $input['is_optional'],
            'assembler_id' => $input['assembler_id'],
        ]);
    }

    // public function getAdditionalFieldsFromGroup($groupId)
    // {
    //     $groupParts = $this->groupPartRepository->getGroupPartsByGroupId($groupId);
    //     if ($groupParts->isEmpty()) {
    //         throw new \Exception('No group parts found for this group.');
    //     }

    //     $versionIds = $groupParts->pluck('version_id')->unique();
    //     if ($versionIds->isEmpty()) {
    //         throw new \Exception('No versions found for this group.');
    //     }
    //     $additionalFields = $this->additionalFieldRepository->getByVersionId($versionIds);
    //     if ($additionalFields->isEmpty()) {
    //         throw new \Exception('No additional fields found for this group.');
    //     }
    //     return $additionalFields;
    // }

    public function getAdditionalFieldsFromGroup($groupId)
    {
        $groupParts = $this->groupPartRepository->getGroupPartsByGroupId($groupId);
        if ($groupParts->isEmpty()) {
            throw new \Exception('No group parts found for this group.');
        }
        $versionIds = $groupParts->pluck('version_id')->unique();
        if ($versionIds->isEmpty()) {
            throw new \Exception('No versions found for this group.');
        }
        $additionalFields = $this->additionalFieldRepository->getAllVersionById($versionIds);
        if ($additionalFields->isEmpty()) {
            throw new \Exception('No additional fields found for this group.');
        }
        $uniqueAdditionalFields = $additionalFields->unique('name')->values();

        return $uniqueAdditionalFields;
    }
}
