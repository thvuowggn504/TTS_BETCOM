<?php

namespace App\GraphQL\Queries;

use App\Services\GeneratedCodeService;
use Illuminate\Support\Facades\Auth;

class GeneratedCodeQuery
{
    protected $generatedCodeService;

    public function __construct(GeneratedCodeService $generatedCodeService)
    {
        $this->generatedCodeService = $generatedCodeService;
    }

    public function validate($_, $args) {
        return $this->generatedCodeService->validateCode($args['codebuilderId']);
    }
}
