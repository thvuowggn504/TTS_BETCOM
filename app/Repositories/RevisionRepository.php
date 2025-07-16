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

    public function createRevisionCode($oldRevision)
    {
        $part = Part::findOrFail($oldRevision->part_id);
        if (!$part) {
            throw new \Exception('Part not found for creating revision code.');
        }

        // Lấy revision mới nhất của part bằng created_at
        $latestRevision = Revision::where('part_id', $part->id)
            ->orderBy('created_at', 'desc')
            ->first();
        if ($latestRevision) {
            $latestRevisionCode = $latestRevision->revision_code;
            // Tạo mã revision_code mới dựa trên mã cũ
            $parts = explode('.', $latestRevisionCode);
            $major = (int) $parts[0];
            $major++;
            $minor = 0;
            return $major . '.' . $minor;
        }
        return '2.0';
    }
}
