<?php

namespace App\Livewire;

use App\Jobs\ScrapeBusinessesJob;
use App\Models\ScrapingJob;
use Illuminate\View\View;
use Livewire\Component;

class Search extends Component
{
    public string $keyword = '';

    public string $location = '';

    public int $limit = 100;

    public function submit(): void
    {
        $this->validate([
            'keyword' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'limit' => 'required|integer|min:1',
        ]);

        $existingJob = ScrapingJob::where('keyword', $this->keyword)
            ->where('location', $this->location)
            ->whereIn('status', ['pending', 'running'])
            ->first();

        if ($existingJob) {
            $this->redirectRoute('result.job', ['id' => $existingJob->id]);

            return;
        }

        $scrapingJob = ScrapingJob::create([
            'keyword' => $this->keyword,
            'location' => $this->location,
            'radius' => 25,
            'source' => 'apify',
            'status' => 'pending',
        ]);

        ScrapeBusinessesJob::dispatch($scrapingJob)->onQueue('default');

        $this->redirectRoute('result');
    }

    public function render(): View
    {
        return view('livewire.search');
    }
}
