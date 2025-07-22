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

    // Lấy version_id mới nhất có trạng thái Published cho một Part
    public function getLatestVersionId($partId)
    {
        // Lấy revision mới nhất theo updated_at
        $latestRevision = Revision::where('part_id', $partId)
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$latestRevision) {
            // Không tìm thấy revision nào thì throw lỗi
            throw new \Exception('No revisions found for part ID ' . $partId);
        }

        // Tìm version có trạng thái Published thuộc revision đó
        $latestVersion = Version::where('revision_id', $latestRevision->id)
            ->where('status', 'Published')
            ->first();

        if ($latestVersion) {
            // Trả về version_id
            return $latestVersion->id;
        }

        // Không tìm thấy version Published thì throw lỗi
        throw new \Exception('No published version found for part ID ' . $partId);
    }

    // Kiểm tra tồn tại bản ghi group_part với group_id và part_id
    public function exists($groupId, $partId)
    {
        return GroupPart::where('group_id', $groupId)
            ->where('part_id', $partId)
            ->exists();
    }

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
}
