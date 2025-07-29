<?php

namespace App\Services;

use App\Http\Requests\CreatePartRequest;
use App\Models\Version;
use App\Repositories\PartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\AdditionalFieldRepository;

class AdditionalFieldService
{
    protected $repository;

    public function __construct(AdditionalFieldRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllVersion($versionId)
    {
        return $this->repository->getAllVersionById($versionId);
    }

    public function getVersion($id)
    {
        return $this->repository->findById($id);
    }
}
