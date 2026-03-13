<?php

namespace App\Livewire;

use App\Models\Business;
use App\Models\ScrapingJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Component;

class Result extends Component
{
    public string $keyword = '';

    public string $location = '';

    public string $search = '';

    public int $currentPage = 1;

    public int $perPage = 5;

    public ?int $jobId = null;

    /** @var array<string, mixed>|null */
    public ?array $scrapingJob = null;

    public function mount(): void
    {
        $this->jobId = (int) request()->query('job_id') ?: null;

        if ($this->jobId) {
            $job = ScrapingJob::find($this->jobId);
            if ($job) {
                $this->keyword = $job->keyword;
                $this->location = $job->location;
                $this->scrapingJob = [
                    'id' => $job->id,
                    'status' => $job->status,
                    'results_count' => $job->results_count,
                ];
            }
        }
    }

    public function getTotalResultsProperty(): int
    {
        return $this->buildQuery()->count();
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->totalResults / $this->perPage));
    }

    /** @return Collection<int, Business> */
    public function getResultsProperty(): Collection
    {
        return $this->buildQuery()
            ->with('businessEmails')
            ->skip(($this->currentPage - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();
    }

    public function updatedSearch(): void
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

    public function viewDetails(int $id): void
    {
        $this->redirectRoute('detail-result', ['id' => $id]);
    }

    public function exportCsv(): void
    {
        $this->redirectRoute('export.csv', $this->exportFilters());
    }

    public function exportExcel(): void
    {
        $this->redirectRoute('export.excel', $this->exportFilters());
    }

    public function render(): View
    {
        return view('livewire.result', [
            'results' => $this->results,
            'totalResults' => $this->totalResults,
            'totalPages' => $this->totalPages,
            'currentPage' => $this->currentPage,
            'perPage' => $this->perPage,
            'keyword' => $this->keyword,
            'location' => $this->location,
        ])->layout('layouts.app', ['title' => 'Scraping Results']);
    }

    private function buildQuery(): Builder
    {
        $query = Business::query();

        if ($this->jobId) {
            $query->where('scraping_job_id', $this->jobId);
        }

        if (! empty($this->search)) {
            $term = '%'.$this->search.'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        return $query->orderByDesc('created_at');
    }

    /** @return array<string, mixed> */
    private function exportFilters(): array
    {
        return array_filter([
            'location' => $this->location ?: null,
        ]);
    }
}
