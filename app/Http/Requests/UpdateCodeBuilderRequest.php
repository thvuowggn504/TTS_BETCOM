<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCodeBuilderRequest extends FormRequest
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
            'id' => 'required|exists:codebuilder,id',
            'name' => 'string|max:255',
            'rule' => 'json',
            // 'rule.*.name' => 'required|string|max:255',
            // 'rule.*.field' => 'required|string|max:255',
            // 'rule.*.value' => 'required|string|max:255',
            // 'version_id' => 'required|exists:versions,id',
            'is_default' => 'boolean',

        ];
    }
}
