<?php

namespace App\GraphQL\Mutations;

use App\Models\Codebuilder;
use App\Models\GeneratedCode;
use App\Services\CodeBuilderService;
use App\Services\GeneratedCodeService;
use Exception;

class CodebuilderRuleResolver
{
    protected $codeBuilderService;
    protected $generatedCodeService;
    public function __construct(CodeBuilderService $codeBuilderService, GeneratedCodeService $generatedCodeService)
    {
        $this->codeBuilderService = $codeBuilderService;
        $this->generatedCodeService = $generatedCodeService;
    }
    public function create($_, array $args)
    {
        $codebuilder = $this->codeBuilderService->create($args['input']);
        $this->generatedCodeService->create(['codebuilder_id' => $codebuilder->id]);
        return $codebuilder;
    }

    public function update($_, array $args)
    {
        return $this->codeBuilderService->update($args['input']);
    }
    public function delete($_, array $args)
    {
        return $this->codeBuilderService->delete($args['id']);
    }

    public function storeRule($_, array $args){
        return $this->codeBuilderService->storeRule($args['input']);
    }

    public function addPropertyToCodebuilder($_, array $args)
    {
        return $this->codeBuilderService->addPropertyToCodebuilder($args['input']);
    }
}
