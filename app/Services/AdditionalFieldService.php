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
    protected $additionalFieldRepository;

    public function __construct(AdditionalFieldRepository $additionalFieldRepository)
    {
        $this->additionalFieldRepository = $additionalFieldRepository;
    }

}
