<?php

namespace App\Services;

use App\Repositories\PartRepository;

class PartService
{
    protected PartRepository $partRepository;

    public function __construct(PartRepository $partRepository)
    {
        $this->partRepository = $partRepository;
    }

    public function createPart(array $input)
    {
        return $this->partRepository->createPart($input);
    }

    public function editPart(array $input)
    {
        return $this->partRepository->editPart($input);
    }

    public function resolveAdditionalFields($version)
    {
        return $this->partRepository->resolveAdditionalFields($version);
    }

    public function updateVersionStatus(int $versionId)
    {
        return $this->partRepository->updateVersionStatus($versionId);
    }
}