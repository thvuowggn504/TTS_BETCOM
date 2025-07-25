<?php

namespace App\Repositories;

use App\Models\Codebuilder;
use App\Models\Part;
use App\Models\Version;
use App\Models\Revision;
use Illuminate\Support\Facades\DB;

class CodeBuilderRepository
{
    public function create(array $data)
    {
        return Codebuilder::create($data);
    }
}
