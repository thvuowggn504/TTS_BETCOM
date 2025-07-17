<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupPart extends Model
{
    public $timestamps = false;

    protected $fillable = ['group_id', 'part_id', 'quantity', 'version_id'];

    // Mỗi group_part thuộc về một Group
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    // Mỗi group_part thuộc về một Part
    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    // Mỗi group_part có một Version
    public function version()
    {
        return $this->belongsTo(Version::class);
    }
}
