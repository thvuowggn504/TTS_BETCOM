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
    
    public function update($id, array $data)
    {
        $codeBuilder = Codebuilder::findOrFail($id);
        $codeBuilder->update($data);
        return $codeBuilder;
    }

    public function delete($id)
    {
        $codeBuilder = $this->findById($id);
        if (!$codeBuilder) {
            throw new \Exception("CodeBuilder not found");
        }
        return $codeBuilder->delete();
    }

    public function findById($id)
    {
        return Codebuilder::find($id);
    }

    public function find($id) {
        return Codebuilder::find($id);
    }
}
