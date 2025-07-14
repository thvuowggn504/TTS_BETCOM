<?php

namespace App\GraphQL\Mutations;

use App\Models\Version;
use App\Services\PartService;

class PartResolver
{
    public function __construct(protected PartService $service) {}

    public function createPart($_, array $args)
    {
        return $this->service->createPart($args['input']);
    }

    public function editPart($_, array $args)
    {
        return $this->service->editPart($args['input']);
    }

    public function updateVersionStatus($_, array $args)
    {
        return $this->service->publishVersion($args['id']);
    }

    public function resolveAdditionalFields(Version $version, array $args)
    {
        $revision = $version->revision;
        return $revision?->part?->additionalFields ?? [];
    }
}
