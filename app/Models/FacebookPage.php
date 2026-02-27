<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookPage extends Model
{
    protected $fillable = [
        'page_id', 'ad_account_id', 'page_name', 'access_token', 'instagram_account_id', 'is_active'
    ];

    protected $hidden = ['access_token'];

    protected $casts = ['is_active' => 'boolean'];
}