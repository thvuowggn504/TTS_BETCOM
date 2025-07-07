<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Type;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    public function index()
    {
        return Type::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:100']);
        return Type::create($validated);
    }

    public function show($id)
    {
        return Type::with('versions')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $type = Type::findOrFail($id);
        $validated = $request->validate(['name' => 'required|string|max:100']);
        $type->update($validated);
        return $type;
    }

    public function destroy($id)
    {
        Type::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
