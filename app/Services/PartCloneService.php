<?php

// namespace App\Services;

// use App\Models\Part;
// use Illuminate\Support\Facades\DB;

// class PartCloneService
// {
//     /**
//      * Nhân bản một Part bao gồm toàn bộ Revision, Version và AdditionalFields
//      * @param int $originalPartId
//      * @param string $newCode
//      * @return Part
//      */
//     public function duplicatePart(int $originalPartId, string $newCode): Part
//     {
//         return DB::transaction(function () use ($originalPartId, $newCode) {
//             // Bước 1: Lấy Part gốc cùng với toàn bộ Revision, Version, AdditionalFields
//             $originalPart = Part::with('revisions.versions.additionalFields')->findOrFail($originalPartId);

//             // Bước 2: Tạo Part mới với code mới và sao chép các thông tin cơ bản
//             $newPart = Part::create([
//                 'name' => $originalPart->name,
//                 'code' => $newCode, // Code mới do FE cung cấp
//                 'description' => $originalPart->description,
//                 'type_id' => $originalPart->type_id,
//             ]);

//             // Bước 3: Duyệt qua từng Revision trong Part gốc
//             foreach ($originalPart->revisions as $originalRevision) {
//                 // Tạo Revision mới gắn với Part mới
//                 $newRevision = $newPart->revisions()->create([
//                     'name' => $originalRevision->name,
//                     'description' => $originalRevision->description,
//                 ]);

//                 // Bước 4: Duyệt qua từng Version trong Revision gốc
//                 foreach ($originalRevision->versions as $originalVersion) {
//                     // Tạo Version mới, luôn đặt status là 'Draft'
//                     $newVersion = $newRevision->versions()->create([
//                         'name' => $originalVersion->name,
//                         'description' => $originalVersion->description,
//                         'status' => 'Draft', // Reset trạng thái về bản nháp
//                         'enable_assembly_groups' => $originalVersion->enable_assembly_groups,
//                     ]);

//                     // Bước 5: Duyệt qua các AdditionalField của version gốc để sao chép
//                     foreach ($originalVersion->additionalFields as $field) {
//                         $newVersion->additionalFields()->create([
//                             'name' => $field->name,
//                             'value' => $field->value,
//                             'data_type' => $field->data_type,
//                             'type_group' => $field->type_group,
//                         ]);
//                     }
//                 }
//             }

//             // Bước 6: Trả về Part mới đã được load đầy đủ các quan hệ
//             return $newPart->load('revisions.versions.additionalFields');
//         });
//     }
// }
