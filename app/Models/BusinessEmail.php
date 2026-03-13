<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessEmail extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'business_id',
        'email',
        'verified',
    ];

    public function casts(): array
    {
        return [
            'verified' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
