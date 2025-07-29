<?php

namespace App\Repositories;

use App\Models\Part;
use App\Models\Version;
use App\Models\Revision;
use Illuminate\Support\Facades\DB;
use App\Models\AdditionalField;

class AdditionalFieldRepository
{
    public function getAllVersionById($versionId)
    {
        return AdditionalField::where('version_id', $versionId)->get();
    }

    public function findById($id)
    {
        return AdditionalField::findOrFail($id);
    }
}
