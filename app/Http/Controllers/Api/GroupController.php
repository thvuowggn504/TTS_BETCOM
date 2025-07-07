<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        return Group::with(['assembler', 'version'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assembler_id' => 'required|exists:parts,id',
            'name' => 'required|string|max:100',
            'version_id' => 'nullable|exists:versions,id',
            'is_optional' => 'boolean',
        ]);

        $group = Group::create($validated);
        return response()->json($group, 201);
    }

    public function show($id)
    {
        return Group::with(['groupParts.part'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'is_optional' => 'boolean'
        ]);

        $group->update($validated);
        return response()->json($group);
    }

    public function destroy($id)
    {
        $group = Group::findOrFail($id);
        $group->delete();
        return response()->json(null, 204);
    }
}
