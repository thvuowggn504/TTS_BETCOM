<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revision extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'part_id',
        'revision_code',
        'latest_version',
        'created_by', 
        'created_at',
        'updated_at'
    ];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function latestVersion()
    {
        return $this->belongsTo(Version::class, 'latest_version');
    }

    public function versions()
    {
        return $this->hasMany(Version::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
