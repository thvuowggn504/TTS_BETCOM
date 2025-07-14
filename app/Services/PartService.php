<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Repositories\{PartRepository, RevisionRepository, VersionRepository, AdditionalFieldRepository};

class PartService
{
    public function __construct(
        protected PartRepository $partRepo,
        protected RevisionRepository $revisionRepo,
        protected VersionRepository $versionRepo,
        protected AdditionalFieldRepository $fieldRepo
    ) {}

    public function createPart(array $input)
    {
        return DB::transaction(function () use ($input) {
            if ($this->partRepo->existsByCode($input['code'])) {
                throw new \Exception('This code already exists!');
            }

            $part = $this->partRepo->create([
                'name' => $input['name'],
                'code' => $input['code'],
                'type_id' => $input['type_id'],
                'description' => $input['description'] ?? null,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $revision = $this->revisionRepo->create([
                'part_id' => $part->id,
                'revision_code' => '1.0',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $version = $this->versionRepo->create([
                'revision_id' => $revision->id,
                'version_code' => 'v1.0',
                'name' => $input['name'],
                'code' => $input['code'],
                'type_id' => $input['type_id'],
                'status' => 'Draft',
                'enable_assembly_groups' => $input['enable_assembly_groups'],
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->revisionRepo->update($revision, ['latest_version' => $version->id]);

            if (!empty($input['additional_fields'])) {
                $this->fieldRepo->createMany($input['additional_fields'], $version->id);
            }

            return $part;
        });
    }

    public function editPart(array $input)
    {
        return DB::transaction(function () use ($input) {
            $part = $this->partRepo->find($input['id']);

            if ($this->partRepo->existsByCode($input['code'], $part->id)) {
                throw new \Exception('This code already exists!');
            }

            $this->partRepo->update($part, [
                'name' => $input['name'] ?? $part->name,
                'code' => $input['code'] ?? $part->code,
                'description' => $input['description'] ?? $part->description,
                'type_id' => $input['type_id'] ?? $part->type_id,
                'updated_at' => now(),
            ]);

            $revision = $this->revisionRepo->getFirstByPartId($part->id) ?? $this->revisionRepo->create([
                'part_id' => $part->id,
                'revision_code' => 'R1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $count = $revision->versions()->count();
            $revisionNumber = intval(str_replace('R', '', $revision->revision_code));
            $versionCode = 'v' . $revisionNumber . '.' . $count;

            $version = $this->versionRepo->create([
                'revision_id' => $revision->id,
                'version_code' => $versionCode,
                'name' => $part->name,
                'code' => $part->code,
                'description' => $part->description,
                'type_id' => $part->type_id,
                'status' => 'Draft',
                'enable_assembly_groups' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->revisionRepo->update($revision, ['latest_version' => $version->id]);

            if (!empty($input['additional_fields'])) {
                $this->fieldRepo->updateOrCreateMany($input['additional_fields'], $version->id);
            }

            return $version;
        });
    }

    public function publishVersion(int $versionId)
    {
        return DB::transaction(function () use ($versionId) {
            $userId = 2;

            $version = $this->versionRepo->find($versionId);
            $this->versionRepo->update($version, [
                'status' => 'Published',
                'created_by' => $userId,
                'updated_at' => now(),
            ]);

            $this->versionRepo->archiveOtherVersions($version->revision_id, $version->id);

            $revision = $this->revisionRepo->find($version->revision_id);
            $this->revisionRepo->update($revision, [
                'latest_version' => $version->id,
                'created_by' => $userId,
                'updated_at' => now(),
            ]);

            $this->partRepo->update($revision->part, [
                'created_by' => $userId,
                'updated_at' => now(),
            ]);

            return $version->fresh();
        });
    }
}
