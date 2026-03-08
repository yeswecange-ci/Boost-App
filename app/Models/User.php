<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar', 'is_active',
        'two_factor_secret', 'two_factor_enabled',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected $casts = [
        'is_active'           => 'boolean',
        'two_factor_enabled'  => 'boolean',
    ];

    public function boostRequests()
    {
        return $this->hasMany(BoostRequest::class, 'operator_id');
    }

    public function validatedBoosts()
    {
        return $this->hasMany(BoostRequest::class, 'validator_id');
    }

    public function facebookPages(): BelongsToMany
    {
        return $this->belongsToMany(FacebookPage::class, 'facebook_page_user');
    }

    /**
     * Retourne null si admin (pas de filtre), sinon array d'IDs DB des pages assignées.
     */
    public function scopedFacebookPageIds(): ?array
    {
        if ($this->hasRole('admin')) return null;
        return $this->facebookPages()->pluck('facebook_pages.id')->toArray();
    }
}