<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public $timestamps = false;

    protected $fillable = ['name', 'email', 'password', 'role'];

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
