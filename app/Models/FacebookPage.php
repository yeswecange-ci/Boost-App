<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FacebookPage extends Model
{
    protected $fillable = [
        'page_id', 'ad_account_id', 'page_name', 'access_token', 'instagram_account_id', 'is_active'
    ];

    protected $hidden = ['access_token'];

    protected $casts = ['is_active' => 'boolean'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'facebook_page_user');
    }
}