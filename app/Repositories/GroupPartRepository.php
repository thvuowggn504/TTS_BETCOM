<?php

namespace App\Repositories;

use App\Models\Part;
use App\Models\Version;
use App\Models\Revision;
use App\Models\GroupPart;
use Illuminate\Support\Facades\DB;

class GroupPartRepository
{
    // Tạo mới một bản ghi trong bảng group_parts
    public function createGroupPart(array $data)
    {
        return GroupPart::create($data);
    }

    // Cập nhật một GroupPart theo ID
    public function updateGroupPart($id, array $data)
    {
        // Tìm group_part theo ID, nếu không có sẽ throw ModelNotFoundException
        $groupPart = GroupPart::findOrFail($id);

        // Cập nhật dữ liệu
        $groupPart->update($data);

        // Trả về bản ghi sau khi cập nhật
        return $groupPart;
    }

    // Xoá một GroupPart theo ID
    public function deleteGroupPart($id)
    {
        // Tìm group_part theo ID, nếu không có sẽ throw ModelNotFoundException
        $groupPart = GroupPart::findOrFail($id);

        // Thực hiện xoá
        return $groupPart->delete();
    }

    // Trả về version_id mới nhất có Published status theo revision.updated_at
    public function getLatestVersionId($partId)
    {
        $latestVersion = Version::select('versions.id')
            ->join('revisions', 'versions.revision_id', '=', 'revisions.id')
            ->where('revisions.part_id', $partId)
            ->where('versions.status', 'Published')
            ->orderByDesc('revisions.updated_at')
            ->limit(1)
            ->first();

        return $latestVersion?->id; // Trả về null nếu không có
    }

    // Lấy tất cả group-part đã tồn tại (map để check nhanh)
    public function getExistingGroupParts($groupIds, $partIds)
    {
        return GroupPart::whereIn('group_id', $groupIds)
            ->whereIn('part_id', $partIds)
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->group_id . '|' . $item->part_id => true
            ])
            ->toArray();
    }

    // // Kiểm tra tồn tại bản ghi group_part với group_id và part_id
    // public function exists($groupId, $partId)
    // {
    //     return GroupPart::where('group_id', $groupId)
    //         ->where('part_id', $partId)
    //         ->exists();
    // }

    // Tìm group theo ID
    public function findGroupById(int $id)
    {
        return \App\Models\Group::find($id);
    }

    // Xoá bản ghi trong bảng group_parts bằng raw query (trả về true nếu xoá thành công)
    public function deleteGroupPartById(int $id): bool
    {
        return DB::table('group_parts')->where('id', $id)->delete() > 0;
        // delete() trả về số dòng bị xoá → > 0 nghĩa là có dòng bị xoá thành công
    }

    // Lấy tất cả group_parts theo group_id
    public function getGroupPartsByGroupId($groupId)
    {
        return GroupPart::where('group_id', $groupId)->get();
    }
}
