<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialLink extends Model
{
    protected $fillable = [
        'business_id',
        'platform',
        'url',
        'is_active',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
