<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'cid',
        'completeness_score',
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

    public function collaborationEmailDraft(): HasOne
    {
        return $this->hasOne(CollaborationEmailDraft::class);
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(SocialLink::class);
    }

    /**
     * Generate a deduplication hash from name, address, and city.
     */
    public static function generateDedupHash(string $name, string $address, string $city = ''): string
    {
        return md5(strtolower(trim($name)).'|'.strtolower(trim($address)).'|'.strtolower(trim($city)));
    }

    /**
     * Get the description or a generated one.
     */
    /**
     * Get the country name, falling back to the scraping job's location or city if missing.
     */
    public function getEffectiveCountry(): string
    {
        if ($this->country && strtoupper($this->country) !== 'US') {
            return $this->country;
        }

        // 1. Try to derive from scraping job location
        if ($this->scrapingJob && $this->scrapingJob->location) {
            $location = strtolower($this->scrapingJob->location);

            // Basic check to see if job location is likely not US
            $usKeywords = ['usa', 'united states', 'new york', 'california', 'texas', 'florida'];
            $isLikelyUS = false;
            foreach ($usKeywords as $kw) {
                if (str_contains($location, $kw)) {
                    $isLikelyUS = true;
                    break;
                }
            }

            if ((! $isLikelyUS && $this->country === 'US') || ! $this->country) {
                return $this->deriveCountryFromLocationString($this->scrapingJob->location);
            }
        }

        // 2. Fallback: try to derive from the city name
        if ($this->city) {
            return $this->deriveCountryFromLocationString($this->city);
        }

        return $this->country ?? 'Unknown';
    }

    /**
     * Common derivation logic from any location string (city or job location).
     */
    private function deriveCountryFromLocationString(?string $location): string
    {
        if (! $location) {
            return 'Unknown';
        }

        $location = trim($location);

        $mappings = [
            'Dubai' => 'United Arab Emirates',
            'Abu Dhabi' => 'United Arab Emirates',
            'UAE' => 'United Arab Emirates',
            'London' => 'United Kingdom',
            'UK' => 'United Kingdom',
            'India' => 'India',
            'Surat' => 'India',
            'Mumbai' => 'India',
            'Delhi' => 'India',
            'Bangalore' => 'India',
            'Pune' => 'India',
            'Ahmedabad' => 'India',
            'Paris' => 'France',
            'Tokyo' => 'Japan',
        ];

        foreach ($mappings as $key => $country) {
            if (stripos($location, $key) !== false) {
                return $country;
            }
        }

        return $location;
    }

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
