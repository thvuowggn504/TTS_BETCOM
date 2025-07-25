<?php

namespace App\Services;

use App\Http\Requests\CreatePartRequest;
use App\Models\Version;
use App\Repositories\PartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\AdditionalFieldRepository;
use App\Repositories\CodeBuilderRepository;


class CodeBuilderService
{
    protected $codeBuilderRepository;
    
    public function __construct(CodeBuilderRepository $codeBuilderRepository)
    {
        $this->codeBuilderRepository = $codeBuilderRepository;
    }

    public function create(array $data)
    {
        $codeBuilderData = [
            'name' => $data['name'] ?? 'unnamed code pattern',
            'rule' => $data['rule'] ?? null,
            'version_id' => $data['version_id'],
            'is_default' => $data['is_default'] ?? false,
            
        ];
        return $this->codeBuilderRepository->create($codeBuilderData);
    }
}
