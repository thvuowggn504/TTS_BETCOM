<?php

namespace App\GraphQL\Queries;

use App\Models\Version;
use App\Models\Revision;
use App\Models\AdditionalField;
use App\Models\Part;
use Illuminate\Support\Facades\DB;

class PartResolver
{
    public function createPart($_, array $args) {
        return DB::transaction(function () use ($args) {
        // Lấy revision_code lớn nhất
        $latestRevision = Revision::orderByDesc('id')->first();
        $nextRevisionNumber = $latestRevision ? intval(explode('.', $latestRevision->revision_code)[0]) + 1 : 1;
        $revisionCode = $nextRevisionNumber . '.0';

        // Tạo revision mới
        $revision = Revision::create([
            'revision_code' => $revisionCode
        ]);

        // Tạo version mới với version_code = revision_code luôn (ví dụ: 1.0)
        $version = Version::create([
            'version_code' => $revisionCode,
            'status' => 'Published',
            'revision_id' => $revision->id
        ]);

        // Tạo Part
        $part = Part::create([
            'name' => $args['name'],
            'description' => $args['description'],
            'revision_id' => $revision->id
        ]);

        return $part;
    });
    }
}
