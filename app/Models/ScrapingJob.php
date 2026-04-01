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
        'error_message',
        'limit',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function ($job) {
            \Illuminate\Support\Facades\Log::info("ScrapingJob #{$job->id} created automatically!", [
                'keyword' => $job->keyword,
                'location' => $job->location,
                'source' => $job->source,
                'backtrace' => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15))
                    ->map(fn($item) => ($item['file'] ?? 'internal') . ':' . ($item['line'] ?? '?'))
                    ->toArray()
            ]);
        });
    }

    public function casts(): array
    {
        return [
            'radius' => 'integer',
            'results_count' => 'integer',
            'limit' => 'integer',
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

    public function markAsFailed(?string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage !== null && $errorMessage !== '' ? mb_substr($errorMessage, 0, 1000) : null,
        ]);
    }

    public function markForRerun(): void
    {
        // Delete old results so the results page only shows data from the new run
        $this->businesses()->delete();

        $this->update([
            'status' => 'pending',
            'results_count' => 0,
            'error_message' => null,
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
