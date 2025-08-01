<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedCode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'codebuilder_id',
        'group_id',
        'version_id',
        'part_id',
        'generated_code'
    ];

    public function part()
    {
        return $this->belongsTo(Part::class, 'part_id');
    }

    public function codebuilder()
    {
        return $this->belongsTo(Codebuilder::class, 'codebuilder_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
