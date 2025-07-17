<?php

namespace App\Services;

use App\Http\Requests\CreatePartRequest;
use App\Models\Version;
use App\Repositories\PartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PartService
{
    protected $partRepository;

    public function __construct(PartRepository $partRepository)
    {
        $this->partRepository = $partRepository;
    }

    /**
     * Tạo một Part mới kèm Revision và Version mặc định
     */
    public function createPart(array $input)
    {
        return DB::transaction(function () use ($input) {
            // Tạo request để thực hiện validate giống như controller
            $request = new CreatePartRequest();
            $request->merge($input);
            $request->setMethod('POST');

            // Thực hiện validate dữ liệu đầu vào
            $validator = Validator::make($request->all(), $request->rules());
            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            $data = $validator->validated();

            // Kiểm tra mã code đã tồn tại hay chưa
            if ($this->partRepository->partExistsByCode($data['code'])) {
                throw new \Exception('Mã code đã tồn tại!');
            }

            // Tạo Part mới
            $part = $this->partRepository->createPart([
                'name' => $data['name'],
                'code' => $data['code'],
                'type_id' => $data['type_id'],
                'description' => $data['description'] ?? null,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Tạo Revision đầu tiên cho Part
            $revision = $this->partRepository->createRevision([
                'part_id' => $part->id,
                'revision_code' => '1.0',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Tạo Version đầu tiên (v1.0) cho Revision
            $version = $this->partRepository->createVersion([
                'revision_id' => $revision->id,
                'version_code' => 'v1.0',
                'name' => $data['name'],
                'code' => $data['code'],
                'type_id' => $data['type_id'],
                'status' => 'Draft',
                'enable_assembly_groups' => $data['enable_assembly_groups'],
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Gán version vừa tạo làm version mới nhất cho revision
            $this->partRepository->updateRevision($revision->id, ['latest_version' => $version->id]);

            // Nếu cho phép group, tạo Group mặc định
            if ($version->enable_assembly_groups) {
                $this->partRepository->createGroup([
                    'assembler_id' => $part->id,
                    'name' => 'Default Group',
                    'version_id' => $version->id,
                    'is_optional' => false,
                ]);
            }

            // Tạo các trường mở rộng (additional fields) nếu có
            if (!empty($data['additional_fields'])) {
                foreach ($data['additional_fields'] as $field) {
                    $this->partRepository->createAdditionalField([
                        'name' => $field['name'],
                        'value' => $field['value'],
                        'version_id' => $version->id,
                        'data_type' => strtolower($field['data_type'] ?? 'string'),
                        'type_group' => strtolower($field['type_group'] ?? 'custom'),
                    ]);
                }
            }

            return $part;
        });
    }

    /**
     * Cập nhật Part bằng cách tạo một version mới từ version hiện tại
     */
    public function updatePart(array $input)
    {
        return DB::transaction(function () use ($input) {
            $latestVersion = $this->partRepository->getVersionWithRelations($input['version_id']);

            if (!$latestVersion || !$latestVersion->revision || !$latestVersion->revision->part) {
                throw new \Exception('Không tìm thấy version, revision hoặc part.');
            }

            $revision = $latestVersion->revision;
            $part = $revision->part;

            // Kiểm tra trạng thái version hợp lệ
            if (!in_array($latestVersion->status, ['Draft', 'Archived'])) {
                throw new \Exception('Chỉ được chỉnh sửa version ở trạng thái Draft hoặc Archived.');
            }

            // Kiểm tra trùng mã code nếu có thay đổi
            if (!empty($input['code']) && $input['code'] !== $latestVersion->code) {
                if ($this->partRepository->partExistsByCode($input['code'], $part->id)) {
                    throw new \Exception('Mã code đã tồn tại!');
                }
            }

            // Nếu có version Draft khác trong cùng revision => chuyển nó thành Archived
            $this->partRepository->archiveDraftVersionIfExists($revision->id, $latestVersion->id);

            // Chuyển version hiện tại (đang sửa) thành Archived
            $this->partRepository->updateVersion($latestVersion->id, [
                'status' => 'Archived',
                'updated_at' => now(),
            ]);

            // Tạo version_code mới: ví dụ "1.2"
            [$revMajor] = explode('.', $revision->revision_code);
            $versionCount = $this->partRepository->countVersionsByRevision($revision->id);
            $versionCode = $revMajor . '.' . $versionCount;

            // Tạo version mới với trạng thái Draft
            $newVersion = $this->partRepository->createVersion([
                'revision_id'            => $revision->id,
                'version_code'           => $versionCode,
                'name'                   => $input['name'] ?? $latestVersion->name,
                'code'                   => $input['code'] ?? $latestVersion->code,
                'description'            => $input['description'] ?? $latestVersion->description,
                'type_id'                => $input['type_id'] ?? $latestVersion->type_id,
                'status'                 => 'Draft',
                'enable_assembly_groups' => $latestVersion->enable_assembly_groups,
                'based_upon_version_id'  => $latestVersion->id,
                'created_by'             => 1,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            // Cập nhật latest_version cho revision
            $this->partRepository->updateRevision($revision->id, [
                'latest_version' => $newVersion->id,
                'updated_at' => now(),
            ]);

            // Ghi additional_fields nếu có
            if (!empty($input['additional_fields'])) {
                foreach ($input['additional_fields'] as $field) {
                    $this->partRepository->createAdditionalField([
                        'name'        => $field['name'],
                        'value'       => $field['value'],
                        'version_id'  => $newVersion->id,
                        'data_type'   => strtolower($field['data_type'] ?? 'string'),
                        'type_group'  => strtolower($field['type_group'] ?? 'custom'),
                    ]);
                }
            }

            return $newVersion;
        });
    }

    /**
     * Lấy danh sách các trường mở rộng của version
     */
    public function getAdditionalFields(Version $version)
    {
        return $version->additionalFields ?? [];
    }

    /**
     * Đổi trạng thái một version thành Published và archive các version khác
     */
    public function updateVersionStatus($versionId)
    {
        return DB::transaction(function () use ($versionId) {
            $userId = 2; // ID mặc định của người thực hiện

            $version = $this->partRepository->getVersion($versionId);
            if (!$version) {
                throw new \Exception('Version not found.');
            }

            // Archive các version khác thuộc cùng revision
            $this->partRepository->archiveOtherVersions($version->revision_id, $version->id);

            // Cập nhật trạng thái version thành Published
            $this->partRepository->updateVersion($version->id, [
                'status'     => 'Published',
                'updated_at' => now(),
                'created_by' => $userId,
            ]);



            // Cập nhật lại revision với version mới nhất
            $this->partRepository->updateRevision($version->revision_id, [
                'latest_version' => $version->id,
                'updated_at'     => now(),
                'created_by'     => $userId,
            ]);

            // Cập nhật dữ liệu Part từ version mới
            $part = $this->partRepository->getPartByRevision($version->revision_id);
            $this->partRepository->updatePart($part->id, [
                'name'        => $version->name,
                'description' => $version->description,
                'code'        => $version->code,
                'type_id'     => $version->type_id,
                'updated_at'  => now(),
                'created_by'  => $userId,
            ]);

            return $this->partRepository->getVersion($version->id);
        });
    }

    /**
     * Lấy version có thể hiển thị công khai (Published) cho một Part
     */
    public function getVisibleVersion($partId)
    {
        return $this->partRepository->getVisibleVersion($partId);
    }
}
