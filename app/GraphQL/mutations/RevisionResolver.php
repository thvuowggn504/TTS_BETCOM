<?php

namespace App\GraphQL\Mutations;

use App\Models\Revision;
use App\Models\Version;
use Illuminate\Support\Facades\DB;
use App\Services\RevisionService;

class RevisionResolver
{
    protected $revisionService;

    public function __construct(RevisionService $revisionService)
    {
        $this->revisionService = $revisionService;
    }

    public function createRevision($_, array $args)
    {
        return $this->revisionService->createRevisionFromVersion($args);
    }
}
