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

    public function updateVersion($_, array $args)
    {
        $input = $args['input'];
        return $this->versionService->updateVersion($input['id'], [
            'name' => $input['name'],
            // 'code' => $input['code'],
            // 'description' => $input['description'],
            // 'status' => $input['status'],
            // 'created_by' => $input['created_by'] ?? 2, // ID mặc định của người thực hiện
        ]);
    }

    public function searchVersionsAssembler($_, array $args)
    {
        $filters = $args['filter'] ?? [];
        return $this->versionService->searchVersionsAssembler($filters);
    }
}
