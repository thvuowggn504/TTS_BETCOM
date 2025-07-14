<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalField extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'value', 'data_type', 'version_id', 'type_group'];

    public function versionId() {
        return $this->belongsTo(Version::class);
    }
}
