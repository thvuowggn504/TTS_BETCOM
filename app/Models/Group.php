<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'assembler_id',
        'name',
        'type_id',
        'version_id',
        'is_optional',
    ];

    /**
     * Group thuộc về một Part (assembler).
     */
    public function assembler()
    {
        return $this->belongsTo(Part::class, 'assembler_id');
    }

    /**
     * Group thuộc về một Version.
     */
    public function version()
    {
        return $this->belongsTo(Version::class);
    }

    /**
     * Group có nhiều GroupPart (các Part thuộc Group).
     */
    public function groupParts()
    {
        return $this->hasMany(GroupPart::class);
    }

    /**
     * Group có một Type.
     */
    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * Các part trong group thông qua bảng trung gian group_parts.
     */
    public function parts()
    {
        return $this->belongsToMany(Part::class, 'group_parts');
    }
}
