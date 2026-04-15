<?php

namespace App\Livewire;

use App\Jobs\ScrapeBusinessesJob;
use App\Models\ScrapingJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Result extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $keyword = '';

    #[Url(as: 'l')]
    public string $location = '';

    #[Url(as: 'c')]
    public string $category = '';

    /**
     * Handle updating of filter properties to reset pagination.
     */
    public function updatedKeyword(): void
    {
        $this->resetPage();
    }

    public function updatedLocation(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    /**
     * Reset all filters.
     */
    public function resetFilters(): void
    {
        $this->reset(['keyword', 'location', 'category']);
        $this->resetPage();
    }

    public function rerunJob(int $jobId): void
    {
        $job = ScrapingJob::find($jobId);

        if (! $job) {
            $this->redirectRoute('result');

            return;
        }

        $job->markForRerun();
        ScrapeBusinessesJob::dispatch($job)->onQueue('default');

        session()->flash('message', 'Job #'.$job->id.' has been queued to run again.');
    }

    #[On('cancel-job')]
    public function cancelJob(int $jobId): void
    {
        $job = ScrapingJob::find($jobId);

        if (! $job || ! in_array($job->status, ['pending', 'running'])) {
            return;
        }

        $job->markAsCancelled();

        session()->flash('message', 'Job #'.$job->id.' has been cancelled.');
    }

    #[On('delete-job')]
    public function deleteJob(int $jobId): void
    {
        $job = ScrapingJob::find($jobId);

        if (! $job) {
            session()->flash('message', 'Job not found.');

            return;
        }

        if ($job->status === 'running') {
            session()->flash('delete_error', 'Job #'.$job->id.' is currently running. Cancel it first before deleting.');

            return;
        }

        $jobNumber = $job->id;

        // Hard delete: cascade through all child relationships
        foreach ($job->businesses as $business) {
            $business->businessEmails()->delete();
            $business->socialLinks()->delete();
            $business->collaborationEmailDraft()->delete();
            $business->delete();
        }

        $job->delete();

        session()->flash('message', 'Job #'.$jobNumber.' and all its data have been permanently deleted.');
    }

    #[Computed]
    public function jobs(): LengthAwarePaginator
    {
        return ScrapingJob::query()
            ->when($this->keyword, function ($query, $keyword) {
                $query->where('keyword', 'like', "%{$keyword}%");
            })
            ->when($this->location, function ($query, $location) {
                $query->where('location', 'like', "%{$location}%");
            })
            ->when($this->category, function ($query, $category) {
                $query->whereHas('businesses', function ($query) use ($category) {
                    $query->where('category', 'like', "%{$category}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return ! empty($this->keyword) || ! empty($this->location) || ! empty($this->category);
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
        return view('livewire.result', [
            'jobs' => $this->jobs,
        ])->layout('layouts.app', ['title' => 'Scraping Jobs']);
    }
}
