<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Version;
use Illuminate\Support\Facades\DB;

class VersionController extends Controller
{
    public function index()
    {
        return Version::with('revision.part')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'revision_id' => 'required|exists:revisions,id',
            'version_code' => 'required|string|max:30',
            'name' => 'required|string|max:100',
            'code' => 'required|string',
            'based_upon_version_id' => 'nullable|exists:versions,id',
            'description' => 'nullable|string',
            'type_id' => 'nullable|exists:type,id',
            'status' => 'in:Draft,Published,Archived',
            'enable_assembly_groups' => 'boolean',
            'created_by' => 'nullable|exists:users,id'
        ]);

        $version = Version::create($validated);
        return response()->json($version, 201);
    }

    public function show($id)
    {
        return Version::with(['revision', 'type', 'codebuilderRules'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $version = Version::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'code' => 'sometimes|string',
            'description' => 'nullable|string',
            'status' => 'in:Draft,Published,Archived',
            'enable_assembly_groups' => 'boolean'
        ]);

        $version->update($validated);
        return response()->json($version);
    }

    public function destroy($id)
    {
        $version = Version::findOrFail($id);
        $version->delete();
        return response()->json(null, 204);
    }

    public function publish(Request $request, $id)
    {
        $version = Version::with('revision.versions')->findOrFail($id);

        if (!in_array($version->status, ['Draft', 'Archived'])) {
            return response()->json(['error' => 'Only draft or archived versions can be published.'], 400);
        }

        DB::transaction(function () use ($version) {
            // Unpublish old version
            Version::where('revision_id', $version->revision_id)
                ->where('status', 'Published')
                ->update(['status' => 'Archived']);

            // Publish this version
            $version->update(['status' => 'Published']);

            // Set as latest
            $version->revision->update(['latest_version' => $version->id]);
        });

        return response()->json($version->fresh());
    }
}
