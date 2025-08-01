<?php

namespace App\Repositories;

use App\Models\GeneratedCode;
use App\Models\Part;
use App\Models\Version;
use App\Models\Revision;
use Illuminate\Support\Facades\DB;

class GeneratedCodeRepository
{
    public function create(array $data)
    {
        return GeneratedCode::create($data);
    }
    
    public function update($id, array $data)
    {
        $generatedCode = GeneratedCode::findOrFail($id);
        $generatedCode->update($data);
        return $generatedCode;
    }

    public function delete($id)
    {
        $generatedCode = $this->find($id);
        if (!$generatedCode) {
            throw new \Exception("Generated Code not found");
        }
        return $generatedCode->delete();
    }

    public function deleteWhere($codebuilderId) {
        $generatedCode = GeneratedCode::where('codebuilder_id', $codebuilderId);
        if (!$generatedCode) {
            throw new \Exception("Generated Code not found");
        }
        return $generatedCode->delete();
    }

    public function find($id) {
        return GeneratedCode::findOrFail($id);
    }
}
