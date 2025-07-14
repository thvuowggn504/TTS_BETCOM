<?php

namespace App\Repositories;

use App\Models\Version;

class VersionRepository
{
    public function create(array $data): Version
    {
        return Version::create($data);
    }

    public function countByRevision(int $revisionId): int
    {
        return Version::where('revision_id', $revisionId)->count();
    }

    public function update(Version $version, array $data): bool
    {
        return $version->update($data);
    }

    public function archiveOtherVersions(int $revisionId, int $excludeId)
    {
        Version::where('revision_id', $revisionId)
            ->where('id', '!=', $excludeId)
            ->update(['status' => 'Archived', 'updated_at' => now()]);
    }

    public function find(int $id): Version
    {
        return Version::findOrFail($id);
    }
}
