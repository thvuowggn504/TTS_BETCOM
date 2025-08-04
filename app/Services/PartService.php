<?php

namespace App\Services;

use App\Http\Requests\CreatePartRequest;
use App\Http\Requests\EditPartRequest;
use App\Models\Group;
use App\Models\Part;
use App\Models\Version;
use App\Repositories\PartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\VersionRepository;
use App\Repositories\RevisionRepository;

class PartService
{
    protected $partRepository;
    protected $versionRepository;
    protected $revisionRepository;
    protected $codeBuilderService;
    protected $groupPartService;

    public function __construct(
        PartRepository $partRepository,
        VersionRepository $versionRepository,
        RevisionRepository $revisionRepository,
        CodeBuilderService $codeBuilderService,
        GroupPartService $groupPartService
    ) {
        $this->groupPartService = $groupPartService;
        $this->partRepository = $partRepository;
        $this->versionRepository = $versionRepository;
        $this->revisionRepository = $revisionRepository;
        $this->codeBuilderService = $codeBuilderService;
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
                throw new \Exception('Code already exists!');
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
                'version_code' => '1.0',
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'type_id' => $data['type_id'],
                'status' => ($data['addToGroup'] == false) ? 'Draft' : 'Published',
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
                $this->codeBuilderService->create([
                    'name' => 'unnamed code pattern',
                    'rule' => null,
                    'version_id' => $version->id,
                    'is_default' => true,
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
     * Cập nhật Part bằng cách tạo một version mới từ version hiện tại.
     * Nếu version hiện tại là Published thì sẽ clone sang Draft trước khi cập nhật.
     */
    public function updatePart(array $input)
    {
        return DB::transaction(function () use ($input) {
            // Validate
            if (empty($input['version_id'])) {
                throw new \Exception('Thiếu version_id!');
            }

            // Lấy version hiện tại
            $currentVersion = $this->partRepository->getVersionWithRelations($input['version_id']);
            if (!$currentVersion || !$currentVersion->revision || !$currentVersion->revision->part) {
                throw new \Exception('Version hoặc các quan hệ liên quan không tồn tại.');
            }

            $revision = $currentVersion->revision;
            $part = $revision->part;

            // Nếu version hiện tại là Published, clone hoặc lấy draft để sửa
            if ($currentVersion->status === 'Published') {
                $existingDraft = $revision->versions()->where('status', 'Draft')->latest()->first();

                if ($existingDraft) {
                    $currentVersion = $existingDraft;
                } else {
                    $currentVersion = $this->clonePublishedVersion($currentVersion->id);
                }
            }

            $updates = [];

            // Các field cơ bản
            foreach (['name', 'code', 'description', 'type_id'] as $field) {
                if (array_key_exists($field, $input)) {
                    // Trường hợp riêng: validate trùng code
                    if ($field === 'code' && $input['code'] !== $currentVersion->code) {
                        if ($this->partRepository->partExistsByCode($input['code'], $part->id)) {
                            throw new \Exception('Mã code đã tồn tại!');
                        }
                    }

                    $updates[$field] = $input[$field];
                }
            }

            // Toggle enable_assembly_groups nếu có yêu cầu
            if (array_key_exists('enable_assembly_groups', $input)) {
                // Toggle giá trị hiện tại
                $updates['enable_assembly_groups'] = !$currentVersion->enable_assembly_groups;
            }

            // Nếu có field cần update
            if (!empty($updates)) {
                $updates['updated_at'] = now();
                $this->partRepository->updateVersion($currentVersion->id, $updates);
            }

            // Additional fields
            if (array_key_exists('additional_fields', $input)) {
                $inputFieldNames = [];

                foreach ($input['additional_fields'] as $field) {
                    $inputFieldNames[] = $field['name'];

                    $this->partRepository->updateOrCreateAdditionalField([
                        'version_id' => $currentVersion->id,
                        'name'       => $field['name'],
                    ], [
                        'value'      => $field['value'],
                        'data_type'  => strtolower($field['data_type'] ?? 'string'),
                        'type_group' => strtolower($field['type_group'] ?? 'custom'),
                        'updated_at' => now(),
                    ]);
                }

                // // Xoá các additional_fields không còn trong input
                // $existingFields = $currentVersion->additionalFields->pluck('name')->toArray();
                // $fieldsToDelete = array_diff($existingFields, $inputFieldNames);

                // if (!empty($fieldsToDelete)) {
                //     $this->partRepository->deleteAdditionalFieldsByNames($currentVersion->id, $fieldsToDelete);
                // }
            }

            return $currentVersion->refresh();
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

            return $version;
        });
    }

    /**
     * Lấy version có thể hiển thị công khai (Published) cho một Part
     */
    public function getVisibleVersion($partId)
    {
        return $this->partRepository->getVisibleVersion($partId);
    }

    public function deleteDraftVersion($versionId): bool
    {
        return DB::transaction(function () use ($versionId) {
            $version = $this->versionRepository->findById($versionId);

            if (!$version) {
                throw new \Exception('Không tìm thấy version.');
            }

            if ($version->status !== 'Draft') {
                throw new \Exception('Chỉ được xoá version ở trạng thái Draft.');
            }

            $revisionId = $version->revision_id;
            $versionCount = $this->versionRepository->countVersionsByRevision($revisionId);

            if ($versionCount <= 1) {
                throw new \Exception('Không thể xoá version duy nhất trong revision.');
            }

            // Nếu version hiện tại là latest, ta cần tính toán lại latestVersion sau khi xóa
            $revision = $this->versionRepository->findRevisionById($revisionId);
            $isLatest = $revision->latest_version == $version->id;

            // Xoá additional_fields
            $this->versionRepository->deleteAdditionalFields($version->id);

            // Xoá version
            $deleted = $this->versionRepository->deleteVersion($version->id);

            // Nếu là latest thì cập nhật lại
            if ($isLatest) {
                $newLatest = $this->versionRepository->getLatestVersionByRevision($revisionId);

                if ($newLatest) {
                    $this->versionRepository->updateRevision($revisionId, [
                        'latest_version' => $newLatest->id,
                        'updated_at' => now(),
                    ]);
                }
            }

            return $deleted;
        });
    }


    public function getPublishedParts()
    {
        return Part::with(['revisions' => function ($query) {
            $query->orderByDesc('updated_at')->limit(1); // chỉ lấy revision mới nhất
        }, 'revisions.versions' => function ($query) {
            $query->orderByDesc('created_at'); // lấy version mới nhất trước
        }])->get()->filter(function ($part) {
            $revision = $part->revisions->first();
            if (!$revision) {
                return false; // Không có revision nào → loại
            }

            // Kiểm tra revision có version nào Published không
            return $revision->versions->contains(function ($version) {
                return $version->status === 'Published';
            });
        })->map(function ($part) {
            $revision = $part->revisions->first();
            $version = $revision->versions->firstWhere('status', 'Published')
                ?? $revision->versions->where('status', 'Archived')->sortByDesc('created_at')->first()
                ?? $revision->versions->firstWhere('status', 'Draft');

            $part->selected_version = $version;
            $part->additional_fields = $version?->additionalFields ?? [];
            unset($part->revisions);
            return $part;
        })->values();
    }

    // public function getPublishedPartsForGroup($groupId)
    // {
    //     $usedVersionIds = DB::table('group_parts')
    //         ->where('group_id', $groupId)
    //         ->pluck('version_id')
    //         ->toArray();

    //     $group = Group::findOrFail($groupId);
    //     $excludePartId = $group->assembler_id;

    //     // Lấy tất cả part, ngoại trừ part cha (assembler)
    //     $parts = Part::where('id', '!=', $excludePartId)
    //         ->with(['revisions' => function ($q) {
    //             $q->orderByDesc('updated_at')->limit(1);
    //         }, 'revisions.versions.additionalFields']) // eager load thêm nếu cần
    //         ->get();

    //     // Lọc và chọn version phù hợp
    //     return $parts->filter(function ($part) use ($usedVersionIds) {
    //         $revision = $part->revisions->first();
    //         if (!$revision) return false;

    //         // Kiểm tra có version Published chưa dùng
    //         return $revision->versions->contains(
    //             fn($v) =>
    //             $v->status === 'Published' && !in_array($v->id, $usedVersionIds)
    //         );
    //     })->map(function ($part) use ($usedVersionIds) {
    //         $revision = $part->revisions->first();

    //         // Ưu tiên version Published chưa dùng
    //         $version = $revision->versions
    //             ->filter(fn($v) => $v->status === 'Published' && !in_array($v->id, $usedVersionIds))
    //             ->first()
    //             ?? $revision->versions->where('status', 'Archived')->sortByDesc('created_at')->first()
    //             ?? $revision->versions->firstWhere('status', 'Draft');

    //         $part->selected_version = $version;
    //         $part->additional_fields = $version?->additionalFields ?? [];
    //         unset($part->revisions);
    //         return $part;
    //     })->values();
    // }

    public function getPublishedPartsForGroup($groupId)
    {
        $group = Group::findOrFail($groupId);
        $excludePartId = $group->assembler_id;

        $parts = Part::where('id', '!=', $excludePartId)
            ->with(['revisions' => function ($q) {
                $q->orderByDesc('updated_at')->limit(1);
            }, 'revisions.versions.additionalFields'])
            ->get();

        return $parts->filter(function ($part) {
            $revision = $part->revisions->first();
            if (!$revision) return false;

            return $revision->versions->contains(fn($v) => $v->status === 'Published');
        })->map(function ($part) {
            $revision = $part->revisions->first();
            $version = $revision->versions->firstWhere('status', 'Published');

            $part->selected_version = $version;
            $part->additional_fields = $version?->additionalFields ?? [];
            unset($part->revisions);
            return $part;
        })->values();
    }


    /**
     * Tạo bản sao của một version Published với status là Draft để chỉnh sửa.
     */
    public function clonePublishedVersion($versionId)
    {
        return DB::transaction(function () use ($versionId) {
            $userId = 2;

            $publishedVersion = $this->partRepository->getVersion($versionId);

            if ($publishedVersion->status !== 'Published') {
                throw new \Exception('Only published versions can be cloned.');
            }

            // Tạo bản sao mới (Draft)
            $newVersion = $this->partRepository->createVersion([
                'revision_id'            => $publishedVersion->revision_id,
                'based_upon_version_id'  => $publishedVersion->id,
                'version_code'           => $this->generateNextVersionCode($publishedVersion->revision),
                'name'                   => $publishedVersion->name,
                'description'            => $publishedVersion->description,
                'code'                   => $publishedVersion->code,
                'type_id'                => $publishedVersion->type_id,
                'status'                 => 'Draft',
                'enable_assembly_groups' => $publishedVersion->enable_assembly_groups,
                'created_by'             => $userId,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            // Archive các bản Draft cũ trong revision (trừ bản vừa tạo)
            $this->partRepository->archiveDraftVersionIfExists($publishedVersion->revision_id, $newVersion->id);

            // Sao chép các additional fields
            foreach ($publishedVersion->additionalFields as $field) {
                $this->partRepository->createAdditionalField([
                    'version_id'  => $newVersion->id,
                    'name'        => $field->name,
                    'value'       => $field->value,
                    'data_type'   => $field->data_type,
                    'type_group'  => $field->type_group,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            // Cập nhật lại latest_version cho revision
            $this->partRepository->updateRevision($publishedVersion->revision_id, [
                'latest_version' => $newVersion->id,
                'updated_at'     => now(),
            ]);

            return $newVersion;
        });
    }

    public function searchPart($keyword)
    {
        return Part::where(function ($query) use ($keyword) {
            $query->where('name', 'ilike', '%' . $keyword . '%')
                ->orWhere('code', 'ilike', '%' . $keyword . '%')
                ->orWhere('description', 'ilike', '%' . $keyword . '%');
        })->get();
    }

    public function searchByType($typeId)
    {
        return Part::where('type_id', $typeId)->get();
    }

    public function deletePart(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $part = $this->partRepository->findById($id);

            if (!$part) {
                throw new \Exception('Part not found.');
            }

            $part->delete();

            return true;
        });
    }

    protected function generateNextVersionCode($revision)
    {
        $existingVersions = $revision->versions ?? $this->partRepository->getAllVersionsOfRevision($revision->id);

        if (count($existingVersions) === 0) {
            return '1.0';
        }

        $maxMinor = 0;

        foreach ($existingVersions as $version) {
            $parts = explode('.', $version->version_code);

            // Nếu major == 1 và minor là số
            if (count($parts) === 2 && $parts[0] === '1' && is_numeric($parts[1])) {
                $minor = (int) $parts[1];
                $maxMinor = max($maxMinor, $minor);
            }
        }

        return '1.' . ($maxMinor + 1);
    }

    public function createAndAddPartToGroup(array $input)
    {
        return DB::transaction(function () use ($input) {
            // B1: Tạo Part mới (gọi hàm có sẵn)
            $part = $this->createPart($input);

            // B2: Add part vào group nếu có group_id
            if (!empty($input['group_id'])) {
                $this->groupPartService->createGroupPart([
                    [
                        'group_id' => $input['group_id'],
                        'part_id' => $part->id
                    ]
                ]);
            }

            return $part;
        });
    }
}
