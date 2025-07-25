<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Auth;
use App\Services\GroupPartService;

class GroupPartQuery
{
    protected $groupPartService;

    public function __construct(GroupPartService $groupPartService)
    {
        $this->groupPartService = $groupPartService;
    }
}
