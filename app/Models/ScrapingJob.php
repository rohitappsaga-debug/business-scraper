<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapingJob extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'keyword',
        'location',
        'radius',
        'source',
        'status',
        'results_count',
    ];

    public function casts(): array
    {
        return [
            'radius' => 'integer',
            'results_count' => 'integer',
        ];
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function markAsRunning(): void
    {
        $this->update(['status' => 'running']);
    }

    public function markAsCompleted(int $resultsCount): void
    {
        $this->update([
            'status' => 'completed',
            'results_count' => $resultsCount,
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
