<?php

namespace App\Livewire;

use App\Jobs\ScrapeBusinessesJob;
use App\Models\Business;
use App\Models\ScrapingJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class JobResults extends Component
{
    public int $id = 0;

    public string $search = '';
    public string $city = '';
    public string $state = '';
    public string $country = '';

    public int $currentPage = 1;

    public int $perPage = 10;

    public function mount(int $id): void
    {
        $this->id = $id;

        if (! $this->job) {
            session()->flash('error', 'Job #' . $id . ' not found or has been deleted.');
            $this->redirectRoute('result');
            return;
        }
    }

    #[Computed]
    public function job(): ?ScrapingJob
    {
        return ScrapingJob::find($this->id);
    }

    #[Computed]
    public function isEnriching(): bool
    {
        if ($this->job?->status !== 'completed') {
            return false;
        }

        // Check if there are any jobs in the queue for enrichment
        return \Illuminate\Support\Facades\DB::table('jobs')->count() > 0;
    }

    #[Computed]
    public function totalResults(): int
    {
        return $this->buildQuery()->count();
    }

    #[Computed]
    public function totalPages(): int
    {
        return max(1, (int) ceil($this->totalResults / $this->perPage));
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return !empty($this->search) || !empty($this->city) || !empty($this->state) || !empty($this->country);
    }

    /** @return Collection<int, Business> */
    #[Computed]
    public function results(): Collection
    {
        if (! $this->job) {
            return collect();
        }

        return $this->buildQuery()
            ->with(['businessEmails', 'socialLinks'])
            ->skip(($this->currentPage - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'city', 'state', 'country']);
        $this->currentPage = 1;
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }
    
    public function updatedCity(): void
    {
        $this->currentPage = 1;
    }

    public function updatedState(): void
    {
        $this->currentPage = 1;
    }

    public function updatedCountry(): void
    {
        $this->currentPage = 1;
    }

    public function updatedPerPage(): void
    {
        $this->currentPage = 1;
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function nextPage(): void
    {
        if ($this->currentPage < $this->totalPages) {
            $this->currentPage++;
        }
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = max(1, min($page, $this->totalPages));
    }

    public function viewDetails(int $businessId): void
    {
        $this->redirectRoute('detail-result', ['id' => $businessId]);
    }

    public function exportCsv(): void
    {
        $this->redirectRoute('export.csv', $this->exportFilters());
    }

    public function exportExcel(): void
    {
        $this->redirectRoute('export.excel', $this->exportFilters());
    }

    public function rerun(): void
    {
        if (! $this->job) {
            $this->redirectRoute('result');
            return;
        }

        $this->job->markForRerun();
        ScrapeBusinessesJob::dispatch($this->job)->onQueue('default');

        session()->flash('message', 'Job #'.$this->job->id.' has been queued to run again.');
        $this->redirectRoute('result');
    }

    public function render(): View
    {
        if (! $this->job) {
            abort(404, 'Job not found');
        }

        return view('livewire.job-results', [
            'results' => $this->results,
            'totalResults' => $this->totalResults,
            'totalPages' => $this->totalPages,
            'currentPage' => $this->currentPage,
            'perPage' => $this->perPage,
        ])->layout('layouts.app', ['title' => 'Results for Job #'.$this->id]);
    }

    private function buildQuery(): Builder
    {
        $query = Business::query()
            ->where('scraping_job_id', $this->id)
            ->select('*')
            ->selectRaw("
                (CASE WHEN (email IS NOT NULL AND email != '' AND email != '-') THEN 100 ELSE 0 END +
                 CASE WHEN (website IS NOT NULL AND website != '' AND website != '-') THEN 50 ELSE 0 END +
                 CASE WHEN (phone IS NOT NULL AND phone != '' AND phone != '-') THEN 30 ELSE 0 END +
                 CASE WHEN (EXISTS (SELECT 1 FROM social_links WHERE business_id = businesses.id)) THEN 20 ELSE 0 END) as dynamic_score
            ");

        if (! empty($this->search)) {
            $term = '%'.$this->search.'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('address', 'like', $term);
            });
        }
        
        if (! empty($this->city)) {
            $query->where('city', 'like', '%'.$this->city.'%');
        }

        if (! empty($this->state)) {
            $query->where('state', 'like', '%'.$this->state.'%');
        }

        if (! empty($this->country)) {
            $query->where('country', 'like', '%'.$this->country.'%');
        }

        return $query->orderByDesc('dynamic_score')->orderByDesc('id');
    }

    /** @return array<string, mixed> */
    private function exportFilters(): array
    {
        return array_filter([
            'job_id' => $this->id,
            'search' => $this->search ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'country' => $this->country ?: null,
        ]);
    }
}

