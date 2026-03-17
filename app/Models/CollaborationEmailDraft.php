<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollaborationEmailDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'subject',
        'email_body',
        'generated_by_ai',
    ];

    protected function casts(): array
    {
        return [
            'generated_by_ai' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
