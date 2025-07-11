<?php

namespace App\GraphQL\Queries;

use App\Models\Version;
use App\Models\Part;


class PartQuery
{
    public function getVersion($_, array $args)
    {
        return Version::whereHas('revision', function ($q) use ($args) {
            $q->where('part_id', $args['partId'])
                ->where('id', $args['revisionId']);
        })
            ->where('version_code', $args['versionCode'])
            ->first();
    }

    public function getAllParts($_, array $args)
    {
        return Part::whereHas('revisions.versions', function ($query) {
            $query->whereIn('status', ['Published', 'Archived', 'Draft']);
        })
            ->orderBy('id', 'desc') // 👈 Thêm dòng này để sort Part mới nhất
            ->with([
                'revisions' => function ($revisionQuery) {
                    $revisionQuery->orderBy('updated_at', 'desc')->limit(1)
                        ->with(['versions']);
                },
                'additionalFields'
            ])
            ->get()
            ->map(function ($part) {
                // Lấy revision mới nhất
                $revision = $part->revisions->first();

                if (!$revision) {
                    $part->selected_version = null;
                    return $part;
                }

                // Ưu tiên lấy version: Published → Archived (mới nhất) → Draft
                $version = $revision->versions->firstWhere('status', 'Published')
                    ?? $revision->versions->where('status', 'Archived')->sortByDesc('created_at')->first()
                    ?? $revision->versions->firstWhere('status', 'Draft');

                $part->selected_version = $version;

                // Xoá những thông tin không cần (nếu muốn gọn)
                unset($part->revisions);

                return $part;
            });

    }
    
    public function getPartById($_, array $args)
    {
        return Part::with([
            'revisions.versions',
            'additionalFields',
        ])->find($args['id']);
    }
}
