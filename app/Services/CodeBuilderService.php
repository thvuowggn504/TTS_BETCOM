<?php

namespace App\Services;

use App\Http\Requests\CreatePartRequest;
use App\Http\Requests\StoreRuleRequest;
use App\Http\Requests\UpdateCodeBuilderRequest;
use App\Models\AdditionalField;
use App\Models\Codebuilder;
use App\Models\Group;
use App\Models\Version;
use App\Repositories\PartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\AdditionalFieldRepository;
use App\Repositories\CodeBuilderRepository;
use PhpParser\Node\Stmt\Else_;

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
            'rule' => '{this.code}',
            'rule_data' => json_encode(
                [
                    'group_name' => 'this',
                    'field_name' => 'code',
                ]
            ),
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

        preg_match_all('/\{([a-zA-Z][a-zA-Z0-9]*)\.([a-zA-Z][a-zA-Z0-9]*)\}/', $data['rule'], $matches, PREG_SET_ORDER);
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
        $groupName = 'this';

        if (!empty($data['group_id'])) {
            $group = Group::findOrFail($data['group_id']);
            $groupName = lcfirst(str_replace(' ', '', $group->name));
        }
        $fieldName = lcfirst(str_replace(' ', '', $data['field_name']));

        $newPlaceholder = '{' . $groupName . '.' . $fieldName . '}';

        // Lấy dữ liệu hiện tại của code builder
        $current = $this->codeBuilderRepository->find($data['id']);

        // Nếu chưa có rule, khởi tạo rỗng
        $existingRule = $current->rule ?? '';
        $existingRuleData = json_decode($current->rule_data, true) ?? [];

        // // Nếu placeholder đã tồn tại thì không thêm nữa
        // if (str_contains($existingRule, $newPlaceholder)) {
        //     return $current; // Không cần cập nhật nếu đã có
        // }

        // Cập nhật rule mới và rule_data mới
        $updatedRule = trim($existingRule . $newPlaceholder);
        $updatedRuleData = array_merge($existingRuleData, [
            [
                'group_name' => $groupName,
                'field_name' => $fieldName,
            ]
        ]);

        return $this->storeRule([
            'id' => $data['id'],
            'rule' => $updatedRule,
            'rule_data' => json_encode($updatedRuleData),
        ]);
    }

    public function getCodeBuilderByVersion($id)
    {
        return Codebuilder::with('version')->where('version_id', $id)->get();
    }
}
