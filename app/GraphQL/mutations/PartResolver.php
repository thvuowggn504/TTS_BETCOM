<?php

namespace App\GraphQL\Mutations;

use App\Services\PartService;

class PartResolver
{
    protected $partService;

    public function __construct(PartService $partService)
    {
        $this->partService = $partService;
    }

    public function createPart($_, array $args)
    {
        return $this->partService->createPart($args['input']);
    }

    public function updatePart($_, array $args)
    {
        return $this->partService->updatePart($args['input']);
    }

    public function resolveAdditionalFields($version, array $args)
    {
        return $this->partService->getAdditionalFields($version);
    }

    public function visibleVersion($part, array $args)
    {
        return $this->partService->getVisibleVersion($part->id);
    }

    public function deleteDraftVersion($_, array $args)
    {
        return $this->partService->deleteDraftVersion($args['version_id']);
    }

    public function editPublishedVersion($_, array $args)
    {
        $versionId = $args['version_id'];

        return $this->partService->editPublishedVersion($versionId);
    }

    public function deletePart($_, array $args) {
        return $this->partService->deletePart($args['id']);
    }
}
