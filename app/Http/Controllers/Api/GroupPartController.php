<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupPart;
use Illuminate\Http\Request;

class GroupPartController extends Controller
{
    public function index()
    {
        return GroupPart::with(['group', 'part'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'part_id' => 'required|exists:parts,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $existing = GroupPart::where('group_id', $validated['group_id'])
            ->where('part_id', $validated['part_id'])->first();

        if ($existing) {
            return response()->json(['error' => 'Part already added to group.'], 400);
        }

        $gp = GroupPart::create($validated);
        return response()->json($gp, 201);
    }

    public function show($id)
    {
        return GroupPart::with(['group', 'part'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $gp = GroupPart::findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $gp->update($validated);
        return response()->json($gp);
    }

    public function destroy($id)
    {
        $gp = GroupPart::findOrFail($id);
        $gp->delete();
        return response()->json(null, 204);
    }
}
