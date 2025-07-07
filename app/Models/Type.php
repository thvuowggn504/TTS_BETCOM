<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    public $timestamps = false;

    // 🔧 Thêm dòng này để Laravel hiểu đúng bảng
    protected $table = 'type';

    protected $fillable = ['name'];

    public function versions()
    {
        return $this->hasMany(Version::class);
    }

    public function additionalFields()
    {
        return $this->hasMany(AdditionalField::class);
    }
}
