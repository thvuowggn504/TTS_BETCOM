<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Codebuilder extends Model
{
    use HasFactory;

    protected $table = 'codebuilder';

    protected $fillable = [
        'version_id',
        'name',
        'rule',
        'is_default',
    ];

    protected $casts = [
        'rule' => 'array',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Codebuilder thuộc về một version.
     */
    public function version()
    {
        return $this->belongsTo(Version::class);
    }
}
