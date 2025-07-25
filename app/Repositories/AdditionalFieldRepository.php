<?php

namespace App\Repositories;

use App\Models\Part;
use App\Models\Version;
use App\Models\Revision;
use Illuminate\Support\Facades\DB;

class AdditionalFieldRepository
{
    public function getByVersionId($versionId)
    {
        return DB::table('additional_fields')
            ->where('version_id', $versionId)
            ->get();
    }
}
