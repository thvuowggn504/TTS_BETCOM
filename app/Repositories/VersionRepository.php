<?php

namespace App\Repositories;

use App\Models\Part;
use App\Models\Version;
use App\Models\Revision;
use Illuminate\Support\Facades\DB;

class VersionRepository
{
    // Tạo mới một Version
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

    public function getLatestVersionByRevision($revisionId)
    {
        return Version::where('revision_id', $revisionId)
            ->orderByDesc('created_at')
            ->first();
    }

    public function findRevisionById($revisionId)
    {
        return Revision::find($revisionId);
    }

    public function updateRevision($revisionId, array $data)
    {
        return Revision::where('id', $revisionId)->update($data);
    }

    public function findById($id)
    {
        return Version::find($id);
    }

    public function countVersionsByRevision($revisionId)
    {
        return Version::where('revision_id', $revisionId)->count();
    }

    public function deleteAdditionalFields($versionId)
    {
        return DB::table('additional_fields')->where('version_id', $versionId)->delete();
    }

    public function deleteVersion($versionId)
    {
        return Version::where('id', $versionId)->delete();
    }

    public function findByPartRevisionAndCode(int $partId, int $revisionId, string $versionCode): ?Version
    {
        return Version::where('code', $versionCode)
            ->whereHas('revision', function ($query) use ($revisionId, $partId) {
                $query->where('id', $revisionId)
                    ->where('part_id', $partId);
            })
            ->first();
    }
}
