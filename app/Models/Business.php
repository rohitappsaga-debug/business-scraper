<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'scraping_job_id',
        'name',
        'category',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'phone',
        'website',
        'email',
        'rating',
        'reviews_count',
        'latitude',
        'longitude',
        'source',
        'dedup_hash',
    ];

    public function casts(): array
    {
        return [
            'rating' => 'decimal:1',
            'reviews_count' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function scrapingJob(): BelongsTo
    {
        return $this->belongsTo(ScrapingJob::class);
    }

    public function businessEmails(): HasMany
    {
        return $this->hasMany(BusinessEmail::class);
    }

    /**
     * Generate a deduplication hash from name and address.
     */
    public static function generateDedupHash(string $name, string $address): string
    {
        return md5(strtolower(trim($name)).'|'.strtolower(trim($address)));
    }
}
