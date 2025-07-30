<?php

namespace App\GraphQL\Queries;

use App\Models\Version;
use App\Models\Part;
use App\Models\Group;
use App\Models\GroupPart;
use App\Models\AdditionalField;
use App\Models\Codebuilder;
use App\Services\CodeBuilderService;

class CodeBuilderQuery
{
    protected $codeBuilderService;
    public function __construct(CodeBuilderService $codeBuilderService)
    {
        $this->codeBuilderService = $codeBuilderService;
    }

    public function getCodeBuilderByVersion($_, $args) {
        return $this->codeBuilderService->getCodeBuilderByVersion($args['versionId']);
    }
}
