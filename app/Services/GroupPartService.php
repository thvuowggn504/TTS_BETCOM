<?php

namespace App\Services;

use App\Http\Requests\CreateGroupPartRequest;
use App\Http\Requests\CreatePartRequest;
use App\Http\Requests\EditPartRequest;
use App\Models\Group;
use App\Models\Part;
use App\Models\Version;
use App\Repositories\PartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\VersionRepository;
use App\Repositories\GroupPartRepository;
use App\Repositories\AdditionalFieldRepository;
use COM;
use Illuminate\Support\Collection;

class GroupPartService
{
    protected $groupPartRepository;
    protected $additionalFieldRepository;
    protected $versionRepository;

    public function __construct(
        GroupPartRepository $groupPartRepository,
        VersionRepository $versionRepository,
        AdditionalFieldRepository $additionalFieldRepository
    ) {
        $this->groupPartRepository = $groupPartRepository;
        $this->versionRepository = $versionRepository;
        $this->additionalFieldRepository = $additionalFieldRepository;
    }

    public function createGroupPart(array $inputs)
    {
        $groupParts = [];

        // Lấy toàn bộ groupId và partId duy nhất từ inputs
        $groupId = collect($inputs)->pluck('group_id')->unique();
        $partId = collect($inputs)->pluck('part_id')->unique();

        // Load toàn bộ group trước (tránh gọi trong vòng lặp)
        $groups = Group::whereIn('id', $groupId)->get()->keyBy('id');

        // Load tất cả versionId mới nhất của part 1 lần
        $latestVersionIds = $this->groupPartRepository->getLatestVersionId($partId);

        // Load tất cả groupPart tồn tại để kiểm tra trùng
        $existingGroupParts = $this->groupPartRepository->getExistingGroupParts($groupId, $partId);

        foreach ($inputs as $input) {
            try {
                $request = new CreateGroupPartRequest($input);
                $request->merge($input);
                $request->setMethod('POST');

                $validator = Validator::make($request->all(), $request->rules());
                if ($validator->fails()) continue;

                $data = $validator->validated();

                $group = $groups[$data['group_id']] ?? null;
                if (!$group || $group->part_id == $data['part_id']) continue;

                // Kiểm tra trùng
                $key = $data['group_id'] . '|' . $data['part_id'];
                if (isset($existingGroupParts[$key])) continue;

                // Lấy version_id từ mảng
                $versionId = $latestVersionIds[$data['part_id']] ?? null;
                if (!$versionId) continue;

                // Tạo group part
                $groupPart = $this->groupPartRepository->createGroupPart([
                    'group_id' => $data['group_id'],
                    'part_id' => $data['part_id'],
                    'quantity' => 1,
                    'version_id' => $versionId,
                ]);

                $groupParts[] = $groupPart;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $groupParts;
    }


    // Xoá toàn bộ group theo ID (có sử dụng transaction)
    public function deleteGroup(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $group = $this->groupPartRepository->findGroupById($id);

            if (!$group) {
                throw new \Exception('Group not found'); // Nếu không tìm thấy group thì throw lỗi
            }

            $group->delete(); // Xoá group

            return true; // Xoá thành công
        });
    }

    // Xoá một part trong group dựa theo ID của group_part
    public function deleteGroupPartById(int $id): bool
    {
        return $this->groupPartRepository->deleteGroupPartById($id);
    }
}
