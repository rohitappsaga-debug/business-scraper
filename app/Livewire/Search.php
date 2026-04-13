<?php

namespace App\Livewire;

use App\Jobs\ScrapeBusinessesJob;
use App\Models\ScrapingJob;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Search extends Component
{
    public string $keyword = '';

    public string $location = '';

    public int $limit = 100;

    public bool $isUnlimited = false;

    public function submit(): void
    {
        \Illuminate\Support\Facades\Log::info("Search::submit() triggered", [
            'keyword' => $this->keyword,
            'location' => $this->location,
            'limit' => $this->limit
        ]);

        try {
            $this->validate([
                'keyword' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'limit' => 'exclude_if:isUnlimited,true|required|integer|min:1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::warning("Search validation failed", $e->errors());
            throw $e;
        }

        $existingJob = ScrapingJob::where('keyword', $this->keyword)
            ->where('location', $this->location)
            ->whereIn('status', ['pending', 'running'])
            ->first();

        if ($existingJob) {
            $this->redirectRoute('result.job', ['id' => $existingJob->id]);

            return;
        }

        $limitToSave = $this->isUnlimited ? 999999 : $this->limit;

        $scrapingJob = ScrapingJob::create([
            'keyword' => $this->keyword,
            'location' => $this->location,
            'radius' => 25,
            'source' => 'Hybrid_enriched_v3',
            'limit' => $limitToSave,
            'status' => 'pending',
        ]);

        \Illuminate\Support\Facades\Log::info("Search component created Job #{$scrapingJob->id}", [
            'keyword' => $this->keyword,
            'location' => $this->location
        ]);

        ScrapeBusinessesJob::dispatch($scrapingJob)->onQueue('default');

        $this->redirectRoute('result');
    }

    public function confirmLogout(): void
    {
        $this->dispatch('open-confirm-modal', [
            'title' => 'Logout',
            'message' => 'Are you sure you want to logout of your session?',
            'confirmButton' => 'Logout',
            'type' => 'danger',
            'confirmActionUrl' => route('logout'),
        ]);
    }

    #[On('logout')]
    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('login');
    }

    public function render(): View
    {
        return view('livewire.search')->layout('layouts.app', ['title' => 'Create New Scraping Job']);
    }
}
