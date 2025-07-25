<?php

namespace App\Repositories;

use App\Models\Part;
use App\Models\Version;
use App\Models\Revision;
use Illuminate\Support\Facades\DB;
use App\Models\AdditionalField;

class AdditionalFieldRepository
{
    public function getByVersionId($versionId)
    {
        return AdditionalField::whereIn('version_id', $versionId)->get();
    }
}
