<?php

namespace App\Repositories;

use App\Models\Part;
use App\Models\Version;
use App\Models\Revision;
use App\Models\GroupPart;

class GroupPartRepository
{
    // Tạo mới một GroupPart dựa trên dữ liệu
    public function createGroupPart(array $data)
    {
        return GroupPart::create($data);
    }

    // Cập nhật một GroupPart theo ID
    public function updateGroupPart($id, array $data)
    {
        $groupPart = GroupPart::findOrFail($id);
        $groupPart->update($data);
        return $groupPart;
    }

    // Xoá một GroupPart theo ID
    public function deleteGroupPart($id)
    {
        $groupPart = GroupPart::findOrFail($id);
        return $groupPart->delete();
    }

    // Lấy phiên bản mới nhất của Part được Published
    public function getLatestVersionId($partId)
    {
        $latestRevision = Revision::where('part_id', $partId)
            ->orderBy('updated_at', 'desc')
            ->first();
        if (!$latestRevision) {
            throw new \Exception('No revisions found for part ID ' . $partId);
        }
        $latestVersion = Version::where('revision_id', $latestRevision->id)
            ->where('status', 'Published')
            ->first();

        if ($latestVersion) {
            return $latestVersion->id;
        }
        return throw new \Exception('No published version found for part ID ' . $partId);
    
    }
}
