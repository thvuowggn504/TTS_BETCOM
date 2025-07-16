<?php

namespace App\Services;

use App\Http\Requests\CreatePartRequest;
use App\Models\Version;
use App\Repositories\PartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VersionService
{
    protected $partRepository;

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
}
