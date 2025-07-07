<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdditionalField;

class AdditionalFieldController extends Controller
{
    public function index()
    {
        return AdditionalField::with('type')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'value' => 'nullable|string|max:50',
            'type_id' => 'nullable|exists:type,id',
            'data_type' => 'in:string,int,bool'
        ]);

        return AdditionalField::create($validated);
    }

    public function show($id)
    {
        return AdditionalField::with('type')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $field = AdditionalField::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'value' => 'nullable|string|max:50',
            'type_id' => 'nullable|exists:type,id',
            'data_type' => 'in:string,int,bool'
        ]);

        $field->update($validated);
        return $field;
    }

    public function destroy($id)
    {
        $field = AdditionalField::findOrFail($id);
        $field->delete();
        return response()->json(null, 204);
    }
}
