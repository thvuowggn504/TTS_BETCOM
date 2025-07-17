<?php

namespace App\Services;

use App\Http\Requests\CreateGroupPartRequest;
use App\Http\Requests\CreatePartRequest;
use App\Http\Requests\EditPartRequest;
use App\Models\Version;
use App\Repositories\PartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\VersionRepository;
use App\Repositories\GroupPartRepository;
use COM;

class GroupPartService
{
    protected $groupPartRepository;

    public function __construct(GroupPartRepository $groupPartRepository)
    {
        $this->groupPartRepository = $groupPartRepository;
    }

    // Tạo mới một GroupPart
    public function createGroupPart(array $input)
    {
        $request = new CreateGroupPartRequest($input);
        $request->merge($input);
        $request->setMethod('POST');

        $validator = Validator::make($request->all(), $request->rules());
        if ($validator->fails()) {
            throw new \Exception('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }
        $data = $validator->validated();

        $versionId = $this->groupPartRepository->getLatestVersionId($data['part_id']);
        if (!$versionId) {
            throw new \Exception('No published version found for part ID ' . $data['part_id']);
        }

        $groupPart = $this->groupPartRepository->createGroupPart([
            'group_id' => $data['group_id'],
            'part_id' => $data['part_id'],
            'quantity' => 1,
            'version_id' => $versionId,
        ]);

        return $groupPart;
    }
}
