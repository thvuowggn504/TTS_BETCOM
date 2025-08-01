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
use Exception;
use PhpParser\Node\Expr\Throw_;
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
        $version = Version::findOrFail($data['version_id']);
        $ruleData = [
            ['version' => [
                'id' => $version->id,
                'defaultFields' => [
                    'code' => $version->code,
                ]
            ]]
        ];
        $codeBuilderData = [
            'name' => $data['name'] ?? 'unnamed code pattern',
            'rule' => '{this.code}',
            'rule_data' => json_encode($ruleData, JSON_UNESCAPED_UNICODE),
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
            throw new Exception("Validation failed: " . implode(", ", $validator->errors()->all()));
        }
        return $this->codeBuilderRepository->update($data['id'], $data);
    }

    public function delete($id)
    {
        return $this->codeBuilderRepository->delete($id);
    }

    // public function storeRule(array $data)
    // {
    //     $request = new StoreRuleRequest();
    //     $request->merge($data);
    //     $request->setMethod('POST');
    //     // Validate input
    //     $validator = Validator::make($request->all(), $request->rules());
    //     if ($validator->fails()) {
    //         throw new Exception("Validation failed: " . implode(", ", $validator->errors()->all()));
    //     }

    //     preg_match_all('/\{([a-zA-Z][a-zA-Z0-9]*)\.([a-zA-Z][a-zA-Z0-9]*)\}/', $data['rule'], $matches, PREG_SET_ORDER);
    //     if (empty($matches)) {
    //         throw new Exception("No valid placeholders found in template.");
    //     }

    //     return $this->codeBuilderRepository->update($data['id'], [
    //         'rule' => $data['rule'],
    //         'rule_data' => $data['rule_data'] ?? json_encode(array_map(function ($match) {
    //             return [
    //                 'group_name' => $match[1],
    //                 'field_name' => $match[2],
    //             ];
    //         }, $matches)),
    //     ]);
    // }

    // public function addPropertyToCodebuilder(array $data)
    // {
    //     $groupName = 'this'; // mặc định nếu không có group
    //     $groupId = null;

    //     if (!empty($data['group_id'])) {
    //         $group = Group::findOrFail($data['group_id']);
    //         $groupName = lcfirst(str_replace(' ', '', $group->name));
    //         $groupId = $group->id;
    //     }

    //     $fieldName = lcfirst(str_replace(' ', '', $data['field_name']));
    //     $newPlaceholder = '{' . $groupName . '.' . $fieldName . '}';

    //     // Lấy codebuilder hiện tại
    //     $current = $this->codeBuilderRepository->find($data['id']);
    //     $existingRule = $current->rule ?? '';

    //     // Đảm bảo rule_data là mảng (array)
    //     $existingRuleData = json_decode($current->rule_data, true);
    //     if (!is_array($existingRuleData)) {
    //         $existingRuleData = []; // Nếu không phải mảng, khởi tạo mảng mới
    //     }

    //     // Tạo item mới
    //     $newItem = [];
    //     if (empty($groupId)) {
    //         $partId = $current->version->revision->part->id ?? null;
    //         $newItem = [
    //             'part_id' => $partId,
    //             'name' => $groupName,
    //             'field' => $fieldName,
    //         ];
    //     } else {
    //         $newItem = [
    //             'group_id' => $groupId,
    //             'name' => $groupName,
    //             'field' => $fieldName,
    //         ];
    //     }

    //     // Thêm item mới vào mảng
    //     $existingRuleData[] = $newItem;

    //     $updatedRule = trim($existingRule . $newPlaceholder);

    //     // Lưu dưới dạng JSON array
    //     return $this->storeRule([
    //         'id' => $data['id'],
    //         'rule' => $updatedRule,
    //         'rule_data' => json_encode($existingRuleData, JSON_UNESCAPED_UNICODE),
    //     ]);
    // }

    public function storeRule(array $data)
    {
        $request = new StoreRuleRequest();
        $request->merge($data);
        $request->setMethod('POST');

        // Validate input
        $validator = Validator::make($request->all(), $request->rules());
        if ($validator->fails()) {
            throw new Exception("Validation failed: " . implode(", ", $validator->errors()->all()));
        }

        // Parse toàn bộ rule để xây dựng lại rule_data
        preg_match_all('/\{([a-zA-Z][a-zA-Z0-9]*)\.([a-zA-Z][a-zA-Z0-9]*)\}/', $data['rule'], $matches, PREG_SET_ORDER);

        $ruleData = [];
        $defaultFields = ['name', 'code', 'type']; // Danh sách các field mặc định

        foreach ($matches as $match) {
            $groupName = $match[1];
            $fieldName = $this->formatLabel($match[2]);
            $current = $this->codeBuilderRepository->find($data['id']);

            if ($groupName === 'this') {
                // Xử lý version (không có group)
                $version = $current->version;

                $item = [
                    'version' => [
                        'id' => $version->id
                    ]
                ];

                if (in_array($fieldName, $defaultFields)) {
                    // Xử lý các trường default
                    if ($fieldName === 'type') {
                        $item['version']['defaultFields'] = [
                            'type' => $version->type->name ?? null
                        ];
                    } else {
                        $item['version']['defaultFields'] = [
                            $fieldName => $version->{$fieldName} ?? null
                        ];
                    }
                } else {
                    // Xử lý additional field
                    $additionalField = $version->additionalFields->where('name', $fieldName)->first();
                    $item['version']['additionalFields'] = [
                        $fieldName => $additionalField->value ?? null
                    ];
                }
            } else {
                // Xử lý group
                if (!isset($data['group_id']))
                    throw new Exception('Missing group_id for group rule.');
                $group = Group::with(['groupParts.version'])->findOrFail($data['group_id']);

                $item = [
                    'group' => [
                        'id' => $group->id
                    ]
                ];

                if (in_array($fieldName, $defaultFields)) {
                    // Lấy dữ liệu từ các version trong group
                    $values = [];
                    foreach ($group->groupParts as $groupPart) {
                        $version = $groupPart->version; // Truy cập trực tiếp
                        if ($fieldName === 'type') {
                            $values[] = $version->type->name ?? null;
                        } else {
                            $values[] = $version->{$fieldName} ?? null;
                        }
                    }

                    $item['group']['defaultFields'] = [
                        $fieldName => array_unique(array_filter($values))
                    ];
                } else {
                    // Lấy additional fields từ các version trong group
                    $values = [];
                    foreach ($group->groupParts as $groupPart) {
                        $version = $groupPart->version; // Truy cập trực tiếp
                        $additionalField = $version->additionalFields->where('name', $fieldName)->first();
                        if ($additionalField) {
                            $values[] = $additionalField->value;
                        }
                    }

                    $item['group']['additionalFields'] = [
                        $fieldName => array_unique(array_filter($values))
                    ];
                }
            }

            $ruleData[] = $item;
        }

        return $this->codeBuilderRepository->update($data['id'], [
            'rule' => $data['rule'],
            'rule_data' => json_encode($ruleData, JSON_UNESCAPED_UNICODE)
        ]);
    }

    public function addPropertyToCodebuilder(array $data)
    {
        $current = $this->codeBuilderRepository->find($data['id']);
        if (!$current) {
            throw new Exception("Codebuilder record not found");
        }
        if (!$current->version) {
            throw new Exception("Version relationship not loaded or does not exist");
        }

        $fieldName = $data['field_name'];

        // Danh sách các field mặc định
        $defaultFields = ['name', 'code', 'type'];

        if (empty($data['group_id'])) {
            // Xử lý version (không có group)
            $version = $current->version;

            $newData = [
                'version' => [
                    'id' => $version->id
                ]
            ];

            if (in_array($fieldName, $defaultFields)) {
                // Xử lý các trường default
                if ($fieldName === 'type') {
                    $newData['version']['defaultFields'] = [
                        'type' => $version->type->name ?? null
                    ];
                } else {
                    $newData['version']['defaultFields'] = [
                        $fieldName => $version->{$fieldName} ?? null
                    ];
                }
            } else {
                // Xử lý additional field
                $additionalField = $version->additionalFields->where('name', $fieldName)->first();
                $newData['version']['additionalFields'] = [
                    $fieldName => $additionalField->value ?? null
                ];
            }
        } else {
            // Xử lý group
            $group = Group::with(['groupParts.part.versions'])->findOrFail($data['group_id']);

            $newData = [
                'group' => [
                    'id' => $group->id
                ]
            ];

            if (in_array($fieldName, $defaultFields)) {
                // Lấy dữ liệu từ các version trong group
                $values = [];
                foreach ($group->groupParts as $groupPart) {
                    $version = $groupPart->version; // Truy cập trực tiếp
                    if ($fieldName === 'type') {
                        $values[] = $version->type->name ?? null;
                    } else {
                        $values[] = $version->{$fieldName} ?? null;
                    }
                }

                $newData['group']['defaultFields'] = [
                    $fieldName => array_unique(array_filter($values))
                ];
            } else {
                // Lấy additional fields từ các version trong group
                $values = [];
                foreach ($group->groupParts as $groupPart) {
                    $version = $groupPart->version; // Truy cập trực tiếp
                    $additionalField = $version->additionalFields->where('name', $fieldName)->first();
                    if ($additionalField) {
                        $values[] = $additionalField->value;
                    }
                }

                $newData['group']['additionalFields'] = [
                    $fieldName => array_unique(array_filter($values))
                ];
            }
        }

        // Lấy rule_data hiện tại
        $existingRuleData = json_decode($current->rule_data, true) ?? [];

        // Thêm dữ liệu mới vào mảng hiện có
        $existingRuleData[] = $newData;
        $fieldName = lcfirst(str_replace(' ', '', $data['field_name']));

        // Cập nhật rule
        $groupName = empty($data['group_id']) ? 'this' : lcfirst(str_replace(' ', '', $group->name));
        $newPlaceholder = '{' . $groupName . '.' . $fieldName . '}';
        // echo('old rule: ' . $current->rule);
        // Giữ nguyên các rule cũ và THÊM mới vào cuối
        $updatedRule = $current->rule . $newPlaceholder;
        // echo('new rule: ' . $updatedRule);
        // Lưu vào database
        return $this->storeRule([
            'id' => $data['id'],
            'rule' => $updatedRule,
            'rule_data' => json_encode($existingRuleData, JSON_UNESCAPED_UNICODE),
            'group_id' => isset($group) ? $group->id : null
        ]);
    }

    function formatLabel($input)
    {
        // Xử lý các trường hợp đặc biệt trước
        $output = $input;

        // 1. Xử lý các từ viết tắt trong ngoặc như (No), (ID), (USD)
        $output = preg_replace_callback('/\(([A-Za-z0-9]+)\)/', function ($matches) {
            return '(' . ucfirst($matches[1]) . ')';
        }, $output);

        // 2. Thêm dấu cách trước dấu ngoặc mở
        $output = preg_replace('/([a-z])\(/', '$1 (', $output);

        // 3. Xử lý camelCase thông thường
        $output = preg_replace('/([a-z])([A-Z])/', '$1 $2', $output);

        // 4. Xử lý chữ số
        $output = preg_replace('/([a-zA-Z])([0-9])/', '$1 $2', $output);
        $output = preg_replace('/([0-9])([a-zA-Z])/', '$1 $2', $output);

        // // 5. Viết hoa chữ cái đầu tiên của toàn bộ chuỗi
        // if (!in_array($output, ['name', 'code', 'type', 'height', 'width', 'lenght', 'finish','material', 'class']))
        //     $output = ucfirst($output);
        return $output;
    }

    public function getCodeBuilderByVersion($id)
    {
        return Codebuilder::with('version')->where('version_id', $id)->orderByDesc('id')->get();
    }
}
