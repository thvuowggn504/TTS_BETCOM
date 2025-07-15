<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePartRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:parts,code',
            'type_id' => 'required|exists:type,id',
            'enable_assembly_groups' => 'required|boolean',
            'description' => 'nullable|string',
            'additional_fields' => 'nullable|array',
            'additional_fields.*.name' => 'required_with:additional_fields|string|max:100',
            'additional_fields.*.value' => 'required_with:additional_fields|string|max:255',
            'additional_fields.*.data_type' => 'in:string,int,boolean,select,select-multi',
        ];
    }
}