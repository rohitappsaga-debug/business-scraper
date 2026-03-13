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

    public string $source = 'google_maps';

    public int $limit = 100;

    public function submit(): void
    {
        $this->validate([
            'keyword' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'limit' => 'required|integer|min:10',
            'source' => 'required|string|in:google_maps,yellowpages',
        ]);

        $scrapingJob = ScrapingJob::create([
            'keyword' => $this->keyword,
            'location' => $this->location,
            'radius' => 25,
            'source' => $this->source,
            'status' => 'pending',
        ]);

        ScrapeBusinessesJob::dispatch($scrapingJob)->onQueue('default');

        $this->redirectRoute('result', ['job_id' => $scrapingJob->id]);
    }

    public function render(): View
    {
        return view('livewire.search');
    }
}
