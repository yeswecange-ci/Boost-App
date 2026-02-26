<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'page_ids', 'is_active'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'page_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function boostRequests()
    {
        return $this->hasMany(BoostRequest::class, 'operator_id');
    }

    public function validatedBoosts()
    {
        return $this->hasMany(BoostRequest::class, 'validator_id');
    }
}