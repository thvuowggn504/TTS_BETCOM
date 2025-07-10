<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'created_by', 
        'name', 
        'code', 
        'description', 
        'type_id',
        'created_at',
        'updated_at'
    ];

    // Quan hệ Part được tạo bởi User (creator)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    // Một Part có nhiều Revisions
    public function revisions()
    {
        return $this->hasMany(Revision::class);
    }

    // Một Part có nhiều Group assembler (dùng khóa assembler_id)
    public function assemblerGroups()
    {
        return $this->hasMany(Group::class, 'assembler_id');
    }

    public function additionalFields()
    {
        return $this->hasMany(AdditionalField::class, 'id');
    }

    public function latestVersion()
    {
        return $this->hasOneThrough(
            Version::class,
            Revision::class,
            'part_id',
            'id',
            'id',
            'latest_version'
        )->where('status', 'Published');
    }

}
