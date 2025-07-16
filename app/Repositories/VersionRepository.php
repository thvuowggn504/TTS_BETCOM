<?php

namespace App\Repositories;

use App\Models\Part;
use App\Models\Version;
use App\Models\Revision;

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

}
