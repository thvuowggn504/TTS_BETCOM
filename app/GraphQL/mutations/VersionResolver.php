<?php

namespace App\GraphQL\Mutations;

use App\Services\PartService;
use App\Services\VersionService;

class VersionResolver
{
    protected $versionService;

    // Inject PartService vào resolver
    public function __construct(VersionService $versionService)
    {
        $this->versionService = $versionService;
    }

    // Mutation cập nhật trạng thái của version (ví dụ: từ Draft -> Published)
    public function updateVersionStatus($_, array $args)
    {
        return $this->versionService->updateVersionStatus($args['id']);
    }

    public function getVersionByCode($_, array $args)
    {
        return $this->versionService->getByPartRevisionAndCode(
            $args['part_id'],
            $args['revision_id'],
            $args['version_code']
        );
    }
}
