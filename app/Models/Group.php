<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    public $timestamps = false;

    protected $fillable = ['assembler_id', 'name', 'version_id', 'is_optional'];

    // Mỗi Group thuộc về một Part (assembler)
    public function assembler()
    {
        return $this->belongsTo(Part::class, 'assembler_id');
    }

    // Mỗi Group thuộc về một Version
    public function version()
    {
        return $this->belongsTo(Version::class);
    }

    // Một Group có nhiều GroupPart (các part trong group)
    public function groupParts()
    {
        return $this->hasMany(GroupPart::class);
    }

    // public function parts()
    // {
    //     return $this->belongsToMany(Part::class, 'group_parts');
    // }
}
