<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public $timestamps = false;

    protected $fillable = ['name', 'email', 'password_hash', 'role'];

    // Laravel Auth mặc định, password_hash thành 'password'
    // accessor/mutator để map

    public function parts()
    {
        return $this->hasMany(Part::class, 'created_by');
    }

    public function revisions()
    {
        return $this->hasMany(Revision::class, 'created_by');
    }

    public function versions()
    {
        return $this->hasMany(Version::class, 'created_by');
    }
}
