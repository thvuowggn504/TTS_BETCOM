<?php

namespace App\Repositories;

use App\Models\Revision;

class RevisionRepository
{
    public function create(array $data): Revision
    {
        return Revision::create($data);
    }

    public function getFirstByPartId(int $partId): ?Revision
    {
        return Revision::where('part_id', $partId)->orderBy('id')->first();
    }

    public function update(Revision $revision, array $data): bool
    {
        return $revision->update($data);
    }

    public function find(int $id): Revision
    {
        return Revision::findOrFail($id);
    }
}
