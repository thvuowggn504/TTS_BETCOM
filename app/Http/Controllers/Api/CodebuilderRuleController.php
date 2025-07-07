<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CodebuilderRule;
use Illuminate\Http\Request;

class CodebuilderRuleController extends Controller
{
    public function index()
    {
        return CodebuilderRule::with('version')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'version_id' => 'required|exists:versions,id',
            'rule' => 'required|array'
        ]);

        $rule = CodebuilderRule::create([
            'version_id' => $validated['version_id'],
            'rule' => json_encode($validated['rule']),
        ]);

        return response()->json($rule, 201);
    }

    public function show($id)
    {
        return CodebuilderRule::with('version')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $rule = CodebuilderRule::findOrFail($id);

        $validated = $request->validate([
            'rule' => 'required|array'
        ]);

        $rule->update(['rule' => $validated['rule']]);
        return response()->json($rule);
    }

    public function destroy($id)
    {
        $rule = CodebuilderRule::findOrFail($id);
        $rule->delete();
        return response()->json(null, 204);
    }
}
