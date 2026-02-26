<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $fillable = [
        'boost_request_id',
        'level',
        'action',
        'comment',
        'user_id',
    ];

    public function boostRequest()
    {
        return $this->belongsTo(BoostRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isApproved(): bool { return $this->action === 'approved'; }
    public function isRejected(): bool { return $this->action === 'rejected'; }
}
