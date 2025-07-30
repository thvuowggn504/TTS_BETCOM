<?php

namespace App\GraphQL\Queries;

use App\Models\Version;
use App\Models\Part;
use App\Services\PartService;


class PartQuery
{
    protected $partService;
    public function __construct(PartService $partService)
    {
        $this->partService = $partService;
    }
    public function getVersion($_, array $args)
    {
        // Lấy version theo ID
        return Version::with(['additionalFields'])
            ->where('id', $args['id'])
            ->first();
    }

    public function getAllParts($_, array $args)
    {
        return Part::whereHas('revisions.versions', function ($query) {
            $query->whereIn('status', ['Published', 'Archived', 'Draft']);
        })
            ->orderBy('id', 'desc')
            ->with([
                'revisions' => function ($revisionQuery) {
                    $revisionQuery->orderBy('updated_at', 'desc')->limit(1)
                        ->with(['versions.additionalFields']);
                }
            ])
            ->get()
            ->map(function ($part) {
                $revision = $part->revisions->first();

                if (!$revision) {
                    $part->selected_version = null;
                    $part->additional_fields = [];
                    return $part;
                }

                $version = $revision->versions->firstWhere('status', 'Published')
                    ?? $revision->versions->where('status', 'Archived')->sortByDesc('created_at')->first()
                    ?? $revision->versions->firstWhere('status', 'Draft');

                $part->selected_version = $version;
                $part->additional_fields = $version?->additionalFields ?? [];

                unset($part->revisions);
                return $part;
            });
    }

    public function getPartById($_, array $args)
    {
        return Part::with([
            'revisions.versions.additionalFields',
        ])->find($args['id']);
    }

    public function getLatestVersion($_, array $args)
    {
        $part = Part::findOrFail($args['partId']);

        // Lấy revision có updated_at mới nhất
        $latestRevision = $part->revisions()
            ->orderBy('updated_at', 'desc')
            ->with('latestVersion.additionalFields')
            ->first();

        if (!$latestRevision || !$latestRevision->latestVersion) {
            return null;
        }

        return $latestRevision->latestVersion;
    }

    public function getPublishedParts($_, array $args)
    {
        // return $this->partService->getPublishedParts();
        return $this->partService->getPublishedPartsForGroup($args['groupId']);
    }

    public function searchParts($_, array $args)
    {
        $keyword = $args['keyword'] ?? '';
        return $this->partService->searchPart($keyword);
    }

    public function getPartsByType($_, array $args)
    {
        $typeId = $args['typeId'] ?? null;

        if (!$typeId) {
            return null;
        }

        return $this->partService->searchByType($typeId);
    }

    public function getPartsByPublishedStatus($_, array $args)
    {
        $published = $args['published'] ?? true;

        return Part::whereHas('versions', function ($q) use ($published) {
            $q->where('status', 'Published');
        }, $published ? '>' : '=', 0)->get();
    }

    public function filterParts($root, array $args)
    {
        $query = Part::query();

        if (isset($args['filter']['keyword'])) {
            $keyword = $args['filter']['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'ilike', "%$keyword%")
                    ->orWhere('code', 'ilike', "%$keyword%");
            });
        }

        if (isset($args['filter']['type_id'])) {
            $query->where('type_id', $args['filter']['type_id']);
        }

        if (isset($args['filter']['published'])) {
            $query->whereHas('revisions.versions', function ($q) use ($args) {
                $q->where('status', $args['filter']['published'] ? 'Published' : 'Draft');
            });
        }

        // if (isset($args['filter']['is_assembler'])) {
        //     $query->where('is_assembler', $args['filter']['is_assembler']);
        // }

        if (isset($args['filter']['is_assembler'])) {
            $query->whereHas('revisions.versions', function ($q) use ($args) {
                $q->where('enable_assembly_groups', $args['filter']['is_assembler']);
            });
        }

        return $query->get();
    }
}
