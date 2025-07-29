<?php

namespace App\GraphQL\Mutations;

//use App\Services\PartCloneService;
use App\Services\PartService;

class PartResolver
{
    protected $partService;
    //protected PartCloneService $partCloneService;

    public function __construct(PartService $partService) //,PartCloneService $partCloneService
    {
        $this->partService = $partService;
        //$this->partCloneService = $partCloneService;
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

    public function clonePublishedVersion($_, array $args)
    {
        $versionId = $args['version_id'];

        $this->partService->clonePublishedVersion($versionId);

        return [
            'success' => true,
            'message' => 'Published version edited successfully.',
        ];
    }

    public function deletePart($_, array $args)
    {
        return $this->partService->deletePart($args['id']);
    }

    // public function duplicateFullPart($_, array $args)
    // {
    //     return $this->partCloneService->duplicatePart(
    //         $args['part_id'],
    //         $args['code']
    //     );
    // }
}
