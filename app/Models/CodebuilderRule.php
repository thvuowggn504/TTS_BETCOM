<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodebuilderRule extends Model
{
    public $timestamps = false;

    protected $fillable = ['version_id', 'rule'];

    // Tự động chuyển 'rule' JSON thành array khi truy xuất
    protected $casts = [
        'rule' => 'array',
    ];

    // Quan hệ CodebuilderRule thuộc về Version
    public function version()
    {
        return $this->belongsTo(Version::class);
    }
}
