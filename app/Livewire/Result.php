<?php

namespace App\Livewire;

use App\Jobs\ScrapeBusinessesJob;
use App\Models\ScrapingJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Component;

class Result extends Component
{
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

    public function cancelJob(int $jobId): void
    {
        $job = ScrapingJob::find($jobId);

        if (! $job || ! in_array($job->status, ['pending', 'running'])) {
            return;
        }

        $job->markAsCancelled();

        session()->flash('message', 'Job #'.$job->id.' has been cancelled.');
    }

    public function getJobsProperty(): LengthAwarePaginator
    {
        $page = (int) request()->get('page', 1);

        return ScrapingJob::query()
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'page', $page);
    }

    public function render(): View
    {
        return view('livewire.result', [
            'jobs' => $this->jobs,
        ])->layout('layouts.app', ['title' => 'Scraping Jobs']);
    }
}
