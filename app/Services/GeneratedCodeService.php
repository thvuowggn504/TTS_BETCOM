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
            throw new \Exception("Validation failed: " . implode(", ", $validator->errors()->all()));
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

    public function update(Codebuilder $codebuilder)
    {
        $characterArray = $this->extractTextBetweenBraces($codebuilder->rule);
        if (!empty($characterArray)) {
            
        }
    }

    function extractTextBetweenBraces(string $rule): array
    {
        $results = [];
        preg_match_all('/\}([^{}]*)\{/', $rule, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $results[] = $match;
            }
        }

        return $results;
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

        // 5. Viết hoa chữ cái đầu tiên của toàn bộ chuỗi
        $output = ucfirst(strtolower($output));

        return $output;
    }
}
