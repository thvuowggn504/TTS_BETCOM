<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'revision_id',
        'version_code',
        'name',
        'code',
        'based_upon_version_id',
        'description',
        'type_id',
        'status',
        'enable_assembly_groups',
        'created_by',
        'created_at',
        'updated_at'
    ];

    public function revision()
    {
        return $this->belongsTo(Revision::class);
    }

    public function basedUpon()
    {
        return $this->belongsTo(Version::class, 'based_upon_version_id');
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function codebuilderRules()
    {
        return $this->hasMany(CodebuilderRule::class);
    }

    public function additional_fields()
    {
        return $this->hasMany(AdditionalField::class);
    }
}
