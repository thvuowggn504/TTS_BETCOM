<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditPartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:parts,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_id' => 'required|exists:type,id',
            'additional_fields' => 'nullable|array',
            'additional_fields.*.name' => 'required|string|max:100',
            'additional_fields.*.value' => 'required|string|max:50',
            'additional_fields.*.data_type' => 'nullable|in:string,int,boolean,select,select-multi',
            'additional_fields.*.type_id' => 'nullable|integer|exists:type,id',
        ];
    }
}
