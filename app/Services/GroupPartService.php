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
use PhpParser\Node\Stmt\Continue_;

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

    // public function createGroupPart(array $inputs)
    // {
    //     $groupParts = [];

    //     // Lấy toàn bộ groupId và partId duy nhất từ inputs
    //     $groupIds = collect($inputs)->pluck('group_id')->unique();
    //     if ($groupIds->isEmpty()) {
    //         throw new \Exception('No group IDs provided.');
    //     }
    //     $partIds = collect($inputs)->pluck('part_id')->unique();
    //     if ($partIds->isEmpty()) {
    //         throw new \Exception('No part IDs provided.');
    //     }

    //     // Load toàn bộ group trước (tránh gọi trong vòng lặp)
    //     $groups = Group::whereIn('id', $groupIds)->get()->keyBy('id');

    //     // Load tất cả versionId mới nhất của part 1 lần
    //     $latestVersionIds = $this->groupPartRepository->getLatestVersionId($partIds->toArray());
    //     if ($latestVersionIds->isEmpty()) {
    //         throw new \Exception('No latest versions or parts found.');
    //     }

    //     // Load tất cả groupPart tồn tại để kiểm tra trùng
    //     $existingGroupParts = $this->groupPartRepository->exists($groupIds, $partIds);

    //     foreach ($inputs as $input) {
    //         try {
    //             $request = new CreateGroupPartRequest($input);
    //             $request->merge($input);
    //             $request->setMethod('POST');

    //             $validator = Validator::make($request->all(), $request->rules());
    //             if ($validator->fails()) continue;
    //             // throw new \Exception("Validation failed: " . implode(", ", $validator->errors()->all()));

    //             $data = $validator->validated();

    //             $group = $groups[$data['group_id']] ?? null;
    //             if (!$group || $group->part_id == $data['part_id']) continue;
    //             // throw new \Exception("Invalid group or part association.");

    //             // Kiểm tra trùng
    //             $key = $data['group_id'] . '|' . $data['part_id'];
    //             if (isset($existingGroupParts[$key])) continue;
    //             // throw new \Exception("Group part already exists for group ID {$data['group_id']} and part ID {$data['part_id']}.");

    //             // Lấy version_id từ mảng
    //             $versionId = $latestVersionIds[$data['part_id']] ?? null;
    //             if (!$versionId) continue;
    //             // throw new \Exception("No latest version found for part ID {$data['part_id']}.");

    //             // Tạo group part
    //             $groupPart = $this->groupPartRepository->createGroupPart([
    //                 'group_id' => $data['group_id'],
    //                 'part_id' => $data['part_id'],
    //                 'quantity' => 1,
    //                 'version_id' => $versionId,
    //             ]);

    //             $groupParts[] = $groupPart;
    //         } catch (\Exception $e) {
    //             // throw new \Exception("Error creating group part: " . $e->getMessage());
    //         }
    //     }

    //     return $groupParts;
    // }

    // public function createGroupPart(array $inputs)
    // {
    //     $groupParts = [];

    //     foreach ($inputs as $input) {
    //         try {
    //             // Tạo request cho từng phần tử input
    //             $request = new CreateGroupPartRequest($input);
    //             $request->merge($input);
    //             $request->setMethod('POST');

    //             // Validate input
    //             $validator = Validator::make($request->all(), $request->rules());
    //             if ($validator->fails()) {
    //                 // Bỏ qua phần tử nếu không hợp lệ
    //                 continue;
    //             }

    //             $data = $validator->validated();

    //             $group = Group::find($data['group_id']);
    //             if ($group && $group->part_id == $data['part_id']) {
    //                 // Nếu part hiện tại chính là part cha, không thêm
    //                 continue;
    //             }

    //             // Kiểm tra trùng (group_id + part_id)
    //             if ($this->groupPartRepository->exists($data['group_id'], $data['part_id'])) {
    //                 continue; // Đã tồn tại, bỏ qua
    //             }

    //             // Lấy versionId cho part
    //             $versionId = $this->groupPartRepository->getLatestVersionId($data['part_id']);
    //             if (!$versionId) {
    //                 continue; // Không có version hợp lệ, bỏ qua
    //             }

    //             // Tạo group part
    //             $groupPart = $this->groupPartRepository->createGroupPart([
    //                 'group_id' => $data['group_id'],
    //                 'part_id' => $data['part_id'],
    //                 'quantity' => 1,
    //                 'version_id' => $versionId,
    //             ]);

    //             $groupParts[] = $groupPart;
    //         } catch (\Exception $e) {
    //             // Bỏ qua lỗi từng phần tử, không throw
    //             continue;
    //         }
    //     }

    //     return $groupParts;
    // }
    public function createGroupPart(array $inputs)
    {
        $groupParts = [];
        $processedPairs = []; // Cache để tránh kiểm tra trùng lặp nhiều lần

        foreach ($inputs as $input) {
            try {
                // Kiểm tra dữ liệu đầu vào cơ bản trước khi xử lý
                if (empty($input['group_id']) || empty($input['part_id'])) {
                    continue;
                }

                $groupId = $input['group_id'];
                $partId = $input['part_id'];

                // Kiểm tra trùng lặp sớm bằng cache
                $pairKey = "{$groupId}_{$partId}";
                if (isset($processedPairs[$pairKey])) {
                    continue;
                }

                // Validate input
                $request = new CreateGroupPartRequest($input);
                $validator = Validator::make($request->all(), $request->rules());
                if ($validator->fails()) {
                    $processedPairs[$pairKey] = true; // Đánh dấu đã xử lý dù fail
                    continue;
                }

                $data = $validator->validated();

                // Kiểm tra group và part cha
                $group = Group::find($data['group_id']);
                if ($group && $group->part_id == $data['part_id']) {
                    $processedPairs[$pairKey] = true;
                    continue;
                }

                // Kiểm tra trùng trong database (chỉ khi chưa có trong cache)
                if ($this->groupPartRepository->exists($groupId, $partId)) {
                    $processedPairs[$pairKey] = true;
                    continue;
                }

                // Lấy versionId cho part
                $versionId = $this->groupPartRepository->getLatestVersionId($partId);
                if (!$versionId) {
                    $processedPairs[$pairKey] = true;
                    continue;
                }

                // Tạo group part
                $groupPart = $this->groupPartRepository->createGroupPart([
                    'group_id' => $groupId,
                    'part_id' => $partId,
                    'quantity' => 1,
                    'version_id' => $versionId,
                ]);

                $groupParts[] = $groupPart;
                $processedPairs[$pairKey] = true; // Đánh dấu đã xử lý thành công
            } catch (\Exception $e) {
                // // Ghi log lỗi để debug
                // \Log::error('Error creating group part: ' . $e->getMessage(), [
                //     'input' => $input,
                //     'exception' => $e
                // ]);
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
