<?php

namespace App\Repositories;

use App\Models\Part;
use App\Models\Version;
use App\Models\Revision;
use App\Models\Group;
use App\Models\AdditionalField;

class PartRepository
{
    // Kiểm tra xem mã code đã tồn tại chưa (trừ ID cụ thể nếu đang update)
    public function partExistsByCode($code, $excludePartId = null)
    {
        $query = Part::where('code', $code);
        if ($excludePartId) {
            $query->where('id', '!=', $excludePartId); // loại trừ part đang update
        }
        return $query->exists(); // trả về true/false
    }

    // Tạo mới một Part
    public function createPart(array $data)
    {
        return Part::create($data);
    }

    // Cập nhật một Part theo ID
    public function updatePart($id, array $data)
    {
        $part = Part::findOrFail($id); // tìm part, nếu không thấy sẽ throw 404
        $part->update($data); // cập nhật dữ liệu
        return $part;
    }

    // Tạo một Revision mới
    public function createRevision(array $data)
    {
        return Revision::create($data);
    }

    // Cập nhật một Revision theo ID
    public function updateRevision($id, array $data)
    {
        $revision = Revision::findOrFail($id);
        $revision->update($data);
        return $revision;
    }

    // Tạo một Version mới
    public function createVersion(array $data)
    {
        return Version::create($data);
    }

    // Cập nhật một Version theo ID
    public function updateVersion($id, array $data)
    {
        $version = Version::findOrFail($id);
        $version->update($data);
        return $version;
    }

    // Lấy một version theo ID
    public function getVersion($id)
    {
        return Version::findOrFail($id);
    }

    // Lấy một version kèm theo thông tin liên quan (revision, part)
    public function getVersionWithRelations($id)
    {
        return Version::with('revision.part')->findOrFail($id);
    }

    // Đếm số version trong một revision
    public function countVersionsByRevision($revisionId)
    {
        return Version::where('revision_id', $revisionId)->count();
    }

    // Tạo mới một group (thường dùng khi assembly mode bật)
    public function createGroup(array $data)
    {
        return Group::create($data);
    }

    // Tạo một field bổ sung cho version
    public function createAdditionalField(array $data)
    {
        return AdditionalField::create($data);
    }

    // Chuyển tất cả version khác trong cùng revision sang trạng thái Archived
    public function archiveOtherVersions($revisionId, $versionId)
    {
        Version::where('revision_id', $revisionId)
            ->where('id', '!=', $versionId)
            ->where('status', 'Published') // chỉ archive các version Published
            ->update([
                'status' => 'Archived', // cập nhật trạng thái
                'updated_at' => now(),  // cập nhật thời gian sửa đổi
            ]);
    }

    // Lấy Part từ một Revision
    public function getPartByRevision($revisionId)
    {
        $revision = Revision::findOrFail($revisionId);
        return $revision->part;
    }

    // Lấy version hiển thị (ưu tiên bản Published, fallback về bản Draft)
    public function getVisibleVersion($partId)
    {
        $part = \App\Models\Part::with([
            'revisions.versions' => function ($query) {
                $query->orderByDesc('created_at'); // version mới nhất trước
            }
        ])->findOrFail($partId);

        // Lấy revision mới nhất
        $latestRevision = $part->revisions->sortByDesc('created_at')->first();

        if (!$latestRevision) return null;

        // Ưu tiên bản đã Published
        $published = $latestRevision->versions->firstWhere('status', 'Published');
        if ($published) return $published;

        // Nếu chưa có bản published, trả về bản Draft (nếu có)
        return $latestRevision->versions->firstWhere('status', 'Draft');
    }

    public function archiveDraftVersionIfExists($revisionId, $excludeVersionId)
    {
        Version::where('revision_id', $revisionId)
            ->where('id', '!=', $excludeVersionId)
            ->where('status', 'Draft')
            ->update([
                'status' => 'Archived',
                'updated_at' => now(),
            ]);
    }

    public function findById(int $id)
    {
        return Part::find($id);
    }

    public function getAllVersionsOfRevision($revisionId)
    {
        return Version::where('revision_id', $revisionId)->get();
    }

    public function updateOrCreateAdditionalField(array $conditions, array $values)
    {
        return AdditionalField::updateOrCreate($conditions, $values);
    }

    public function deleteAdditionalFieldsByNames($versionId, array $names)
    {
        return AdditionalField::where('version_id', $versionId)
            ->whereIn('name', $names)
            ->delete();
    }
}
