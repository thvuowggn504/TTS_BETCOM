<?php

namespace App\GraphQL\Mutations;

use App\Models\Codebuilder;
use App\Services\CodeBuilderService;

class CodebuilderRuleResolver
{
    protected $codeBuilderService;
    public function __construct(CodeBuilderService $codeBuilderService)
    {
        $this->codeBuilderService = $codeBuilderService;
    }
    public function create($_, array $args)
    {
        return $this->codeBuilderService->create($args['input']);
    }

    public function update($_, array $args)
    {
        return $this->codeBuilderService->update($args['input']);
    }
    public function delete($_, array $args)
    {
        return $this->codeBuilderService->delete($args['id']);
    }
}
