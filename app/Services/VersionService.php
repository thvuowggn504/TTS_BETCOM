<?php

namespace App\Services;

use App\Http\Requests\CreatePartRequest;
use App\Http\Requests\GetVersionByVersionCodeRequest;
use App\Models\Version;
use App\Repositories\PartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VersionService
{
    protected $partRepository;
    protected $versionRepository;

    public function __construct(PartRepository $partRepository)
    {
        $this->partRepository = $partRepository;
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

    public function getCustomFields($versionId)
    {
        $version = $this->partRepository->getVersion($versionId);
        if (!$version) {
            throw new \Exception('Version not found.');
        }

        return $version->additionalFields->filter(function ($field) {
            return $field->type_group === 'custom';
        });
    }

    public function getStandardFields($versionId)
    {
        $version = $this->partRepository->getVersion($versionId);
        if (!$version) {
            throw new \Exception('Version not found.');
        }

        return $version->additionalFields->filter(function ($field) {
            return $field->type_group === 'standard';
        });
    }

    public function getInheritedFields($versionId)
    {
        $version = $this->partRepository->getVersion($versionId);
        if (!$version) {
            throw new \Exception('Version not found.');
        }

        return $version->additionalFields->filter(function ($field) {
            return $field->type_group === 'inherited';
        });
    }

    public function getVersionByVersionCode($inputs)
    {
        $request = new GetVersionByVersionCodeRequest();
        $request->merge($inputs);
        $request->setMethod('POST');

        // Validate input
        $validator = Validator::make($request->all(), $request->rules());
        if ($validator->fails()) {
            throw new \Exception('Invalid input data. ' . $validator->errors());
        }
        $data = $validator->validated();

        // Tìm Part
        $part = $this->partRepository->findById($data['partId']);
        if (!$part) {
            throw new \Exception('Part not found.');
        }

        // Tìm Revision thuộc Part đó
        $revision = $part->revisions()->where('id', $data['revisionId'])->first();
        if (!$revision) {
            throw new \Exception('Revision not found.');
        }

        // Tìm Version với version_code
        $version = $revision->versions()->where('version_code', $data['versionCode'])->first();
        if (!$version) {
            throw new \Exception('Version not found.');
        }

        return $version;
    }

    public function getByPartRevisionAndCode(int $partId, int $revisionId, string $versionCode): Version
    {
        $version = $this->versionRepository->findByPartRevisionAndCode($partId, $revisionId, $versionCode);

        if (!$version) {
            throw new \Exception('Version not found');
        }

        return $version;
    }
}
