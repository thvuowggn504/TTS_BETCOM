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

    //Lấy tất cả các part đã published
    public function getAllPublished()
    {
        return Part::whereHas('versions', function ($q) {
            $q->where('status', 'Published');
        })->get();
    }

    //Láy tất cả các part chưa published
    public function getAllUnpublished()
    {
        return Part::whereDoesntHave('versions', function ($q) {
            $q->where('status', 'Published');
        })->get();
    }
}
