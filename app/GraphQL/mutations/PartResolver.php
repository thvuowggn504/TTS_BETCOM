<?php

namespace App\GraphQL\Mutations;

use App\Services\PartService;

class PartResolver
{
    protected PartService $partService;

    public function __construct(PartService $partService)
    {
        $this->partService = $partService;
    }

    public function createPart($_, array $args)
    {
        return $this->partService->createPart($args['input']);
    }

    public function editPart($_, array $args)
    {
        return $this->partService->editPart($args['input']);
    }

    public function resolveAdditionalFields($version, array $args)
    {
        return $this->partService->resolveAdditionalFields($version);
    }

    public function updateVersionStatus($_, array $args)
    {
        return $this->partService->updateVersionStatus($args['id']);
    }
}