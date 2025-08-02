<?php

namespace App\Services;

use App\Http\Requests\CreateGeneratedCodeRequest;
use App\Http\Requests\CreatePartRequest;
use App\Models\Codebuilder;
use App\Models\GeneratedCode;
use App\Models\Group;
use App\Models\Part;
use App\Models\Version;
use App\Repositories\PartRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\GeneratedCodeRepository;
use MLL\GraphQLScalars\JSON;

class GeneratedCodeService
{
    protected $repository;

    public function __construct(GeneratedCodeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function create(array $data)
    {
        $request = new CreateGeneratedCodeRequest();
        $request->merge($data);
        $request->setMethod('POST');

        // Validate input
        $validator = Validator::make($request->all(), $request->rules());
        if ($validator->fails()) {
            throw new Exception("Validation failed: " . implode(", ", $validator->errors()->all()));
        }

        $codebuilder = Codebuilder::findOrFail($data['codebuilder_id']);
        $version = $codebuilder->version;
        $generatedCodes = $this->repository->create([
            'generated_code' => $version->code,
            'codebuilder_id' => $codebuilder->id,
            'version_id' => 2,
        ]);
        return $generatedCodes;
    }

    public function update($codebuilderId)
    {
        $codebuilder = Codebuilder::findOrFail($codebuilderId);
        $ruleData = json_decode($codebuilder->rule_data, true);
        $generatedData = $this->generateCodesFromRule($codebuilder->rule, $ruleData);
        if (empty($generatedData))
            throw new Exception('failed to generate Data');
        // Cap nhat: xoa code cu & tao lai
        $this->repository->deleteWhere(['codebuilder_id' => $codebuilder->id]);

        foreach ($generatedData as $code) {
            $this->repository->create([
                'generated_code' => $code,
                'codebuilder_id' => $codebuilder->id,
                'version_id' => $codebuilder->version_id,
            ]);
        }

        return $generatedData;
    }

    function generateCodesFromRule(string $rule, array $ruleData): array
    {
        $fields = $this->extractFieldsInOrder($rule); // Lấy danh sách field theo thứ tự
        $combinations = [[]];

        foreach ($fields as $index => $field) {
            $data = $ruleData[$index] ?? null;
            $values = [];

            if (str_starts_with($field, 'this.')) {
                $key = lcfirst(str_replace('this.', '', $field));
                $val = $data['version']['defaultFields'][$key]
                    ?? $data['version']['additionalFields'][$key]
                    ?? null;
                if ($val !== null) {
                    $values = is_array($val) ? $val : [$val];
                }
            } else {
                $parts = explode('.', $field);
                $key = lcfirst(end($parts));
                $val = $data['group']['defaultFields'][$key]
                    ?? $data['group']['additionalFields'][$key]
                    ?? null;
                if ($val !== null) {
                    $values = is_array($val) ? $val : [$val];
                }
            }

            // Cartesian product theo từng field
            $newCombinations = [];
            foreach ($combinations as $combo) {
                foreach ($values as $value) {
                    $newCombinations[] = array_merge($combo, [$value]);
                }
            }
            $combinations = $newCombinations;
        }

        // Gắn vào rule
        $results = [];
        foreach ($combinations as $combo) {
            $result = $rule;
            foreach ($fields as $i => $field) {
                $result = preg_replace('/\{' . preg_quote($field, '/') . '\}/', $combo[$i], $result, 1);
            }
            $results[] = $result;
        }

        return $results;
    }

    function extractFieldsInOrder(string $rule): array
    {
        preg_match_all('/\{([^{}]+)\}/', $rule, $matches);
        return $matches[1] ?? [];
    }

    function validateCode($codebuilderId)
    {
        $generatedCodes = $this->repository->getByCodeBuilder($codebuilderId);
        foreach ($generatedCodes as $generatedCode) {
            $isContainted = Version::where('code', $generatedCode->generated_code)->exists();
            if ($isContainted)
                return false;
        }
        return true;
    }
}
