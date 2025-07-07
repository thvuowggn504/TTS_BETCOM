<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Part;

class PartController extends Controller
{
    /**
     * Lấy danh sách tất cả các part kèm theo revision.
     */
    public function index()
    {
        $parts = Part::with('revisions.latestVersion')->get();
        return response()->json($parts);
    }

    /**
     * Tạo một part mới.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'created_by' => 'nullable|exists:users,id',
        ]);

        $part = Part::create($validated);

        return response()->json($part, 201);
    }

    /**
     * Lấy thông tin chi tiết của một part theo ID.
     */
    public function show($id)
    {
        $part = Part::with('revisions.versions')->findOrFail($id);
        return response()->json($part);
    }

    /**
     * Cập nhật thông tin một part.
     */
    public function update(Request $request, $id)
    {
        $part = Part::findOrFail($id);

        $validated = $request->validate([
            'created_by' => 'nullable|exists:users,id',
        ]);

        $part->update($validated);

        return response()->json($part);
    }

    /**
     * Xoá một part khỏi hệ thống.
     */
    public function destroy($id)
    {
        $part = Part::findOrFail($id);
        $part->delete();

        return response()->json(null, 204);
    }
}
