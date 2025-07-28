<?php

namespace App\Services;

use App\Http\Requests\CreateRevisionRequest;
use App\Models\Part;
use App\Models\Revision;
use App\Models\Version;
use App\Repositories\PartRepository;
use App\Repositories\RevisionRepository;
use App\Repositories\VersionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RevisionService
{
    protected $revisionRepository;
    protected $versionRepository;

    public function __construct(RevisionRepository $revisionRepository, VersionRepository $versionRepository)
    {
        $this->revisionRepository = $revisionRepository;
        $this->versionRepository = $versionRepository;
    }

    public function createRevisionFromVersion(array $args)
    {
        return DB::transaction(function () use ($args) {
            // Chỉ validate thủ công nếu muốn chắc chắn
            if (empty($args['id']) || !is_numeric($args['id'])) {
                throw new \Exception('Invalid or missing version ID.');
            }

            $versionId = (int) $args['id'];

            $version = Version::findOrFail($versionId);
            if (!$version) {
                throw new \Exception('Version not found with ID ' . $versionId);
            }

            $revision = Revision::findOrFail($version->revision->id);
            if (!$revision) {
                throw new \Exception('Revision not found.');
            }

            // Tạo revision mới
            $revisionData = [
                'part_id' => $revision->part_id,
                'revision_code' => $this->createRevisionCode($revision),
                'latest_version' => null,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $newRevision = $this->revisionRepository->createRevision($revisionData);
            if (!$newRevision) {
                throw new \Exception('Failed to create revision.');
            }

            $versionData = [
                'revision_id' => $newRevision->id,
                'name' => $version->name,
                'code' => $version->code,
                'version_code' => '1.0',
                'description' => $version->description,
                'type_id' => $version->type_id,
                'status' => 'Draft',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $newVersion = $this->versionRepository->createVersion($versionData);
            if (!$newVersion) {
                throw new \Exception('Failed to create version.');
            }

            $this->revisionRepository->updateRevision($newRevision->id, [
                'latest_version' => $newVersion->id,
            ]);

            return $newRevision;
        });
    }

    public function createRevisionCode($oldRevision)
    {
        $part = Part::findOrFail($oldRevision->part_id);
        if (!$part) {
            throw new \Exception('Part not found for creating revision code.');
        }

        // Lấy revision mới nhất của part bằng created_at
        $latestRevision = Revision::where('part_id', $part->id)
            ->orderBy('id', 'desc')
            ->first();
        if ($latestRevision) {
            $latestRevisionCode = $latestRevision->revision_code;
            // Tạo mã revision_code mới dựa trên mã cũ
            $parts = explode('.', $latestRevisionCode);
            $major = (int) $parts[0];
            $major++;
            $minor = 0;
            return $major . '.' . $minor;
        }
        return '2.0';
    }

    public function sortByDesc($field)
    {
        return Revision::orderBy($field, 'desc')->get();
    }
}
