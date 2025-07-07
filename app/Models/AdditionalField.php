<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalField extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'value', 'type_id', 'data_type'];

    // Mỗi AdditionalField thuộc về một Type
    public function type()
    {
        return $this->belongsTo(Type::class);
    }
}
