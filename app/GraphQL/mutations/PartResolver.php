<?php

namespace App\GraphQL\Mutations;

use App\Services\PartService;

class PartResolver
{
    protected $partService;

    // Inject PartService vào resolver
    public function __construct(PartService $partService)
    {
        $this->partService = $partService;
    }

    // Mutation tạo mới một Part (gọi đến Service xử lý logic)
    public function createPart($_, array $args)
    {
        return $this->partService->createPart($args['input']);
    }

    // Mutation cập nhật một Part (bao gồm cả logic tạo version mới)
    public function updatePart($_, array $args)
    {
        return $this->partService->updatePart($args['input']);
    }

    // Resolve thêm các field phụ (AdditionalFields) trong version
    public function resolveAdditionalFields($version, array $args)
    {
        return $this->partService->getAdditionalFields($version);
    }

    // Resolve lấy version hiển thị của một part (ưu tiên bản Published)
    public function visibleVersion($part, array $args)
    {
        return $this->partService->getVisibleVersion($part->id);
    }
}
