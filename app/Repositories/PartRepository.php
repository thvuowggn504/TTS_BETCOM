<?php

namespace App\Repositories;

use App\Models\Part;

class PartRepository
{
    public function create(array $data): Part
    {
        return Part::create($data);
    }

    public function find(int $id): Part
    {
        return Part::findOrFail($id);
    }

    public function existsByCode(string $code, int $excludeId = null): bool
    {
        $query = Part::where('code', $code);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function update(Part $part, array $data): bool
    {
        return $part->update($data);
    }
}
