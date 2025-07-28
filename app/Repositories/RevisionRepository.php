<?php

namespace App\Repositories;

use App\Models\Part;
use App\Models\Version;
use App\Models\Revision;

class RevisionRepository
{
    // Tạo mới một revision dựa trên version
    public function createRevision(array $data)
    {
        return Revision::create($data);
    }

    // Cập nhật một revision theo ID
    public function updateRevision($id, array $data)
    {
        $baseRevision = Revision::findOrFail($id);
        $baseRevision->update($data);
        return $baseRevision;
    }
}
