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
        'description',
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

    /**
     * Get the description or a generated one.
     */
    public function getGeneratedDescription(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $category = strtolower($this->category ?? 'business');
        $locationParts = array_filter([$this->city, $this->state]);
        $location = ! empty($locationParts) ? ' in '.implode(', ', $locationParts) : '';

        return "{$this->name} is a premier {$category}{$location}. They are dedicated to providing excellent service and professional expertise to their valued clients.";
    }
}
