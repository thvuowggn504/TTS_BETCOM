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
use COM;

class GroupPartService
{
    protected $groupPartRepository;

    public function __construct(GroupPartRepository $groupPartRepository)
    {
        $this->groupPartRepository = $groupPartRepository;
    }

    public function createGroupPart(array $inputs)
    {

        $groupParts = [];

        foreach ($inputs as $input) {
            try {
                // Tạo request cho từng phần tử input
                $request = new CreateGroupPartRequest($input);
                $request->merge($input);
                $request->setMethod('POST');

                // Validate input
                $validator = Validator::make($request->all(), $request->rules());
                if ($validator->fails()) {
                    // Bỏ qua phần tử nếu không hợp lệ
                    continue;
                }

                $data = $validator->validated();

                $group = Group::find($data['group_id']);
                if ($group && $group->part_id == $data['part_id']) {
                    // Nếu part hiện tại chính là part cha, không thêm
                    continue;
                }

                // Kiểm tra trùng (group_id + part_id)
                if ($this->groupPartRepository->exists($data['group_id'], $data['part_id'])) {
                    continue; // Đã tồn tại, bỏ qua
                }

                // Lấy versionId cho part
                $versionId = $this->groupPartRepository->getLatestVersionId($data['part_id']);
                if (!$versionId) {
                    continue; // Không có version hợp lệ, bỏ qua
                }

                // Tạo group part
                $groupPart = $this->groupPartRepository->createGroupPart([
                    'group_id' => $data['group_id'],
                    'part_id' => $data['part_id'],
                    'quantity' => 1,
                    'version_id' => $versionId,
                ]);

                $groupParts[] = $groupPart;
            } catch (\Exception $e) {
                // Bỏ qua lỗi từng phần tử, không throw
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
