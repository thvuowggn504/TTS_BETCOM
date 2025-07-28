<?php

namespace App\Services;

use App\Http\Requests\CreatePartRequest;
use App\Http\Requests\StoreRuleRequest;
use App\Http\Requests\UpdateCodeBuilderRequest;
use App\Models\Version;
use App\Repositories\PartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\AdditionalFieldRepository;
use App\Repositories\CodeBuilderRepository;


class CodeBuilderService
{
    protected $codeBuilderRepository;

    public function __construct(CodeBuilderRepository $codeBuilderRepository)
    {
        $this->codeBuilderRepository = $codeBuilderRepository;
    }

    public function create(array $data)
    {
        $codeBuilderData = [
            'name' => $data['name'] ?? 'unnamed code pattern',
            'rule' => $data['rule'] ?? null,
            'version_id' => $data['version_id'],
            'is_default' => $data['is_default'] ?? false,

        ];
        return $this->codeBuilderRepository->create($codeBuilderData);
    }

    public function update(array $data)
    {
        $request = new UpdateCodeBuilderRequest();
        $request->merge($data);
        $request->setMethod('POST');

        // Validate input
        $validator = Validator::make($request->all(), $request->rules());
        if ($validator->fails()) {
            throw new \Exception("Validation failed: " . implode(", ", $validator->errors()->all()));
        }
        return $this->codeBuilderRepository->update($data['id'], $data);
    }

    public function delete($id)
    {
        return $this->codeBuilderRepository->delete($id);
    }

    public function storeRule(array $data)
    {
        $request = new StoreRuleRequest();
        $request->merge($data);
        $request->setMethod('POST');
        // Validate input
        $validator = Validator::make($request->all(), $request->rules());
        if ($validator->fails()) {
            throw new \Exception("Validation failed: " . implode(", ", $validator->errors()->all()));
        }

        preg_match_all('/\{([^\{\}]+?)\.([a-zA-Z0-9_]+)\}/', $data['rule'], $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            throw new \Exception("No valid placeholders found in template.");
        }

        return $this->codeBuilderRepository->update($data['id'], [
            'rule' => $data['rule'], 
            'rule_data' => $data['rule_data'] ?? json_encode(array_map(function ($match) {
                return [
                    'group_name' => $match[1],
                    'field_name' => $match[2],
                ];
            }, $matches)),
        ]);
    }

    public function addPropertyToCodebuilder(array $data)
    {
        return $this->storeRule([
            'id' => $data['id'],
            'rule' => $data['rule'],
            'rule_data' => json_encode([
                [
                    'group_name' => $data['group_name'],
                    'field_name' => $data['field_name'],
                ]
            ]),
        ]);
    }
}
