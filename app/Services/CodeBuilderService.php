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
use App\Repositories\VersionRepository;
use App\Services\GeneratedCodeService;
use Exception;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt\Else_;

class CodeBuilderService
{
    protected $codeBuilderRepository;
    protected $generatedCodeService;
    protected $versionRepository;

    public function __construct(
        CodeBuilderRepository $codeBuilderRepository,
        GeneratedCodeService $generatedCodeService,
        VersionRepository $versionRepository
    ) {
        $this->codeBuilderRepository = $codeBuilderRepository;
        $this->generatedCodeService = $generatedCodeService;
        $this->versionRepository = $versionRepository;
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
        $codebuilder = $this->codeBuilderRepository->create($codeBuilderData);
        $this->generatedCodeService->create(['codebuilder_id' => $codebuilder->id]);
        return $codebuilder;
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

    public function storeRule(array $data)
    {
        $request = new UpdateCodeBuilderRequest();
        $request->merge($data);
        $request->setMethod('POST');

        // Validate input
        $validator = Validator::make($request->all(), $request->rules());
        if ($validator->fails()) {
            throw new Exception("Validation failed: " . implode(", ", $validator->errors()->all()));
        }

        $codebuilder = $this->codeBuilderRepository->find($data['id']);
        if (!$codebuilder) {
            throw new Exception("Codebuilder not found");
        }

        $version = $this->versionRepository->findById($codebuilder->version_id);
        if (!$version) {
            throw new Exception("Version not found!");
        }

        // Danh sách group hiện có
        $allGroups = Group::with(['groupParts.part.versions.additionalFields', 'groupParts.version.additionalFields'])->get();

        // Danh sách field mặc định
        $defaultFields = ['name', 'code', 'type'];

        // Hàm chuyển tên group về dạng camelCase
        $toCamelCase = function (string $str): string {
            $str = preg_replace('/[^a-zA-Z0-9 ]/', '', $str);
            $words = explode(' ', strtolower($str));
            $camel = array_shift($words);
            foreach ($words as $word) {
                $camel .= ucfirst($word);
            }
            return $camel;
        };

        // Tìm group theo camelCase
        $findGroupByCamel = function ($camelName, $versionId) use ($allGroups, $toCamelCase) {
            foreach ($allGroups as $group) {
                if ($toCamelCase($group->name) === $camelName && $group->version->id == $versionId) {
                    return $group;
                }
            }
            return null;
        };

        // Bắt đầu xử lý rule
        $rule = $data['rule'];
        preg_match_all('/\{([^{}]+)\}/', $rule, $matches);
        $placeholders = $matches[1] ?? [];

        if (empty($placeholders)) {
            throw new Exception("Rule must contain at least one valid placeholder.");
        }

        $newRuleData = [];

        foreach ($placeholders as $fieldPath) {
            $parts = explode('.', $fieldPath);
            if (count($parts) !== 2) {
                throw new Exception("Invalid field format: {$fieldPath}");
            }

            [$groupKey, $fieldName] = $parts;
            $fieldName = lcfirst(str_replace(' ', '', $fieldName));

            // Xử lý version
            if ($groupKey === 'this') {
                $item = [
                    'version' => [
                        'id' => $version->id
                    ]
                ];

                if (in_array($fieldName, $defaultFields)) {
                    $item['version']['defaultFields'] = [
                        $fieldName => $fieldName === 'type' ? ($version->type->name ?? null) : ($version->{$fieldName} ?? null)
                    ];
                } else {
                    $newFieldName = $this->formatLabel($fieldName);
                    $field = $version->additionalFields->where('name', $newFieldName)->first();
                    if (!$field) {
                        throw new Exception("Field '{$fieldName}' not found in version additionalFields.");
                    }
                    $item['version']['additionalFields'] = [
                        $fieldName => $field->value ?? null
                    ];
                }

                $newRuleData[] = $item;
            }
            // Xử lý group
            else {
                $group = $findGroupByCamel($groupKey, $version->id);
                // echo("Group '{$group}', groupkey '{$groupKey}'");
                if (!$group) {
                    throw new Exception("Group '{$groupKey}' not found.");
                }

                $item = [
                    'group' => [
                        'id' => $group->id
                    ]
                ];

                $values = [];
                foreach ($group->groupParts as $groupPart) {
                    $versionInGroup = $groupPart->version;

                    if (in_array($fieldName, $defaultFields)) {
                        $values[] = $fieldName === 'type'
                            ? ($versionInGroup->type->name ?? null)
                            : ($versionInGroup->{$fieldName} ?? null);
                    } else {
                        $newFieldName = $this->formatLabel($fieldName);
                        $field = $versionInGroup->additionalFields->where('name', $newFieldName)->first();
                        $values[] = $field ? $field->value : "";
                    }
                }

                $values = array_unique($values);
                if (empty($values)) {
                    throw new Exception("Field '{$fieldName}' not found in group '{$group->name}'");
                }

                if (in_array($fieldName, $defaultFields)) {
                    $item['group']['defaultFields'] = [
                        $fieldName => $values
                    ];
                } else {
                    $item['group']['additionalFields'] = [
                        $fieldName => $values
                    ];
                }

                $newRuleData[] = $item;
            }
        }

        // Cập nhật codebuilder nếu tất cả field đều hợp lệ
        $updated = $this->codeBuilderRepository->update($codebuilder->id, [
            'rule' => $rule,
            'rule_data' => json_encode($newRuleData, JSON_UNESCAPED_UNICODE)
        ]);

        $this->generatedCodeService->update($codebuilder->id);

        return $updated;
    }
    // public function storeRule(array $data)
    // {
    //     $request = new UpdateCodeBuilderRequest();
    //     $request->merge($data);
    //     $request->setMethod('POST');

    //     // Validate input
    //     $validator = Validator::make($request->all(), $request->rules());
    //     if ($validator->fails()) {
    //         throw new Exception("Validation failed: " . implode(", ", $validator->errors()->all()));
    //     }

    //     $codebuilder = $this->codeBuilderRepository->find($data['id']);
    //     $version = $this->versionRepository->findById($codebuilder->version_id);
    //     if (empty($version))
    //         throw new Exception("Version not found!");

    //     $newCodeBuilder = $this->codeBuilderRepository->update($data['id'], [
    //         'rule' => $data['rule']
    //     ]);
    //     $code = $this->generatedCodeService->update($codebuilder->id);
    //     if (empty($code))
    //         throw new Exception("ko cập nhật code khi store codebuilder");

    //     return $newCodeBuilder;
    // }

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
                $fieldName = lcfirst(str_replace(' ', '', $data['field_name']));
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
                    $values[] = $additionalField ? $additionalField->value : "";
                }
                $fieldName = lcfirst(str_replace(' ', '', $data['field_name']));
                $newData['group']['additionalFields'] = [
                    $fieldName => array_unique($values)
                ];
            }
        }

        // Lấy rule_data hiện tại
        $existingRuleData = json_decode($current->rule_data, true) ?? [];

        // Thêm dữ liệu mới vào mảng hiện có
        $existingRuleData[] = $newData;


        // Cập nhật rule
        $groupName = empty($data['group_id']) ? 'this' : lcfirst(str_replace(' ', '', $group->name));
        $newPlaceholder = '{' . $groupName . '.' . $fieldName . '}';
        // Giữ nguyên các rule cũ và THÊM mới vào cuối
        $updatedRule = $current->rule . $newPlaceholder;

        $codebuilder = $this->codeBuilderRepository->update($data['id'], [
            'rule' => $updatedRule,
            'rule_data' => json_encode($existingRuleData, JSON_UNESCAPED_UNICODE),
        ]);

        $this->generatedCodeService->update($codebuilder->id);

        return $codebuilder;
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

    // function splitRuleStringToRule(string $rule): array
    // {
    //     $parts = [];
    //     $buffer = '';
    //     $length = strlen($rule);

    //     for ($i = 0; $i < $length; $i++) {
    //         $char = $rule[$i];

    //         if ($char === '{') {
    //             // Đẩy buffer nếu có
    //             if ($buffer !== '') {
    //                 $parts[] = $buffer;
    //                 $buffer = '';
    //             }
    //             $buffer .= '{';
    //         } elseif ($char === '}') {
    //             $buffer .= '}';
    //             $parts[] = $buffer;
    //             $buffer = '';
    //         } elseif ($char === ' ') {
    //             if ($buffer !== '') {
    //                 $parts[] = $buffer;
    //                 $buffer = '';
    //             }
    //         } else {
    //             $buffer .= $char;
    //         }
    //     }

    //     if ($buffer !== '') {
    //         $parts[] = $buffer;
    //     }

    //     return $parts;
    // }
    // function generateCodeFromRule(array $parts, Version $version, Group $group): string
    // {
    //     $result = '';

    //     foreach ($parts as $part) {
    //         $trimmed = trim($part);

    //         // Kiểm tra phần tử có dạng { ... }
    //         if (strlen($trimmed) >= 2 && $trimmed[0] === '{' && $trimmed[strlen($trimmed) - 1] === '}') {
    //             $content = substr($trimmed, 1, -1); // bỏ dấu { và }
    //             $dotPos = strpos($content, '.');

    //             if ($dotPos !== false) {
    //                 // Có dấu chấm, tách thành phần trước và sau
    //                 $beforeDot = substr($content, 0, $dotPos);
    //                 $afterDot = substr($content, $dotPos + 1);

    //                 $defaultFields = ['name', 'code', 'type'];
    //                 if ($beforeDot === 'this') {
    //                     if (in_array($afterDot, $defaultFields)) {
    //                         // Default
    //                         if ($afterDot === 'type') {
    //                             $afterDot = $version->type->name;
    //                         } else {
    //                             $afterDot = $version->{$afterDot} ?? null;
    //                         }
    //                         $result .= $afterDot;
    //                     } else {
    //                         // Addtional
    //                         $additionalField = $version->additionalFields->where('name', $afterDot)->first();
    //                         $afterDot = $additionalField->value ?? null;
    //                         $result .= $afterDot;
    //                     }
    //                 } else {
    //                     if (in_array($afterDot, $defaultFields)) {
    //                         // Lấy dữ liệu từ các version trong group
    //                         $values = [];
    //                         foreach ($group->groupParts as $groupPart) {
    //                             $version = $groupPart->version; // Truy cập trực tiếp
    //                             if ($afterDot === 'type') {
    //                                 $result .= $version->type->name ?? null;
    //                             } else {
    //                                 $result .= $version->{$afterDot} ?? null;
    //                             }
    //                         }
    //                     } else {
    //                         // Lấy additional fields từ các version trong group
    //                         $values = [];
    //                         foreach ($group->groupParts as $groupPart) {
    //                             $version = $groupPart->version; // Truy cập trực tiếp
    //                             $additionalField = $version->additionalFields->where('name', $afterDot)->first();
    //                             if ($additionalField) {
    //                                 $result .= $additionalField->value;
    //                             }
    //                         }
    //                     }
    //                 }
    //             } else {
    //                 // Không có dấu chấm, giữ nguyên toàn bộ
    //                 $result .= $part;
    //             }
    //         } else {
    //             // Không phải {...}, giữ nguyên
    //             $result .= $part;
    //         }
    //     }

    //     return $result;
    // }
}
