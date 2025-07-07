<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Revision;
use App\Models\Version;
use Illuminate\Support\Facades\DB;

class RevisionController extends Controller
{
    /**
     * Lấy danh sách tất cả các revision cùng với các version của chúng.
     */
    public function index()
    {
        $revisions = Revision::with('versions')->get();
        return response()->json($revisions);
    }

    /**
     * Tạo một revision mới và tạo kèm theo version đầu tiên nếu cần.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'part_id' => 'required|exists:parts,id',
            'revision_code' => 'required|string|max:30',
            'created_by' => 'nullable|exists:users,id',
        ]);

        $revision = Revision::create($validated);

        return response()->json($revision, 201);
    }

    /**
     * Lấy chi tiết một revision kèm các version.
     */
    public function show($id)
    {
        $revision = Revision::with('versions')->findOrFail($id);
        return response()->json($revision);
    }

    /**
     * Cập nhật revision, ví dụ cập nhật version mới nhất.
     */
    public function update(Request $request, $id)
    {
        $revision = Revision::findOrFail($id);

        $validated = $request->validate([
            'revision_code' => 'sometimes|string|max:30',
            'latest_version' => 'nullable|exists:versions,id',
        ]);

        $revision->update($validated);

        return response()->json($revision);
    }

    /**
     * Xoá revision. Nếu có cascade thì version cũng sẽ bị xoá.
     */
    public function destroy($id)
    {
        $revision = Revision::findOrFail($id);
        $revision->delete();

        return response()->json(null, 204);
    }

    /**
     * Tạo revision mới và sao chép version hiện tại thành version đầu tiên của revision này.
     */
    public function createFromVersion(Request $request)
    {
        $validated = $request->validate([
            'version_id' => 'required|exists:versions,id',
            'revision_code' => 'required|string|max:30',
            'created_by' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            $version = Version::findOrFail($validated['version_id']);

            $revision = Revision::create([
                'part_id' => $version->revision->part_id,
                'revision_code' => $validated['revision_code'],
                'created_by' => $validated['created_by'] ?? null,
            ]);

            $newVersion = Version::create([
                'revision_id' => $revision->id,
                'version_code' => '1.0',
                'name' => $version->name,
                'code' => $version->code,
                'based_upon_version_id' => $version->id,
                'description' => $version->description,
                'type_id' => $version->type_id,
                'status' => 'Draft',
                'enable_assembly_groups' => $version->enable_assembly_groups,
                'created_by' => $validated['created_by'] ?? null,
            ]);

            $revision->update(['latest_version' => $newVersion->id]);

            DB::commit();

            return response()->json($revision->load('versions'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
