<?php

namespace App\GraphQL\Queries;

use App\Models\Version;
use App\Models\Part;
use App\Models\Group;
use App\Models\GroupPart;
use App\Services\VersionService;

class VersionQuery
{
    protected $versionService;
    public function __construct(VersionService $versionService)
    {
        $this->versionService = $versionService;
    }

    public function getCustomFields($_, array $args)
    {
        return $this->versionService->getCustomFields($args['versionId']);
    }
    
    public function getStandardFields($_, array $args)
    {
        return $this->versionService->getStandardFields($args['versionId']);
    }

    public function getInheritedFields($_, array $args)
    {
        return $this->versionService->getInheritedFields($args['versionId']);
    }

    public function getVersionByVersionCode($_, array $args)
    {
        return $this->versionService->getVersionByVersionCode($args['input']);
    }
}