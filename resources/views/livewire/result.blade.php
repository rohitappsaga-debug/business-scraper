<div>
<div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100">
<div class="layout-container flex h-full grow flex-col">
<main class="flex flex-1 justify-center py-8 px-6 lg:px-20">
<div class="layout-content-container flex flex-col w-full max-w-[1280px] gap-6">
    <!-- Page Title & Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex flex-col gap-1">
            <h1 class="text-slate-900 dark:text-white text-3xl font-black leading-tight tracking-[-0.033em]">Scraping Jobs</h1>
            <p class="text-slate-500 dark:text-slate-400 text-base font-normal">View and manage your scraping jobs. Click a job to see its results.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('search') }}" class="flex items-center justify-center rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold hover:opacity-90 transition-all shadow-sm">
                <span class="material-symbols-outlined text-sm mr-2">add</span>
                Start New Search
            </a>
        </div>
    </div>

    @if (session('message'))
        <div class="rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-emerald-800 dark:text-emerald-200 text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Jobs Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Job #</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Keyword</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Source</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Results</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Created</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($jobs as $job)
                        @php
                            $status = $job->status;
                            $statusColor = match($status) {
                                'completed' => 'text-emerald-600 dark:text-emerald-400',
                                'failed' => 'text-red-600 dark:text-red-400',
                                'running' => 'text-blue-600 dark:text-blue-400',
                                default => 'text-slate-600 dark:text-slate-400',
                            };
                            $iconBg = match($status) {
                                'completed' => 'bg-emerald-100 dark:bg-emerald-900/30',
                                'failed' => 'bg-red-100 dark:bg-red-900/30',
                                'running' => 'bg-blue-100 dark:bg-blue-900/30',
                                default => 'bg-slate-100 dark:bg-slate-900/30',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-4 text-slate-900 dark:text-white font-bold text-sm">{{ $job->id }}</td>
                            <td class="px-4 py-4 text-slate-900 dark:text-white font-medium text-sm">{{ $job->keyword }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-400 text-sm">{{ $job->location }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-400 text-sm">{{ ucfirst($job->source ?? '—') }}</td>
                            <td class="px-4 py-4">
                                <span class="flex items-center gap-1.5 w-fit rounded-full px-2.5 py-0.5 {{ $iconBg }} {{ $statusColor }} text-xs font-semibold">
                                    @if ($status === 'running')
                                        <span class="material-symbols-outlined text-xs animate-spin">sync</span>
                                    @endif
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-400 text-sm">{{ $job->results_count ?? 0 }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-400 text-sm">{{ $job->created_at?->format('M j, Y g:i A') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <a href="{{ route('result.job', ['id' => $job->id]) }}" class="flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg bg-primary text-white text-sm font-semibold hover:opacity-90 transition-all shadow-sm" title="View results">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        <span>View results</span>
                                    </a>
                                    <button type="button" wire:click="rerunJob({{ $job->id }})" wire:loading.attr="disabled" class="flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition-all" title="Rerun job">
                                        <span class="material-symbols-outlined text-[18px]" wire:loading.remove wire:target="rerunJob">replay</span>
                                        <span class="material-symbols-outlined text-[18px] animate-spin" wire:loading wire:target="rerunJob">sync</span>
                                        <span>Rerun</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-400 dark:text-slate-600 text-sm">No jobs yet. <a href="{{ route('search') }}" class="text-primary font-semibold hover:underline">Start a new search</a>.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($jobs->hasPages())
            <div class="flex items-center justify-between p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                <p class="text-slate-500 dark:text-slate-400 text-sm">
                    Showing {{ $jobs->firstItem() }} to {{ $jobs->lastItem() }} of {{ $jobs->total() }} jobs
                </p>
                <div>
                    {{ $jobs->withQueryString()->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
</main>
<footer class="mt-auto py-8 px-20 border-t border-slate-200 dark:border-slate-800 text-center text-slate-400 dark:text-slate-600 text-xs">
    &copy; 2024 LeadScraper Pro. All rights reserved. | <a class="hover:text-primary transition-colors" href="#">Privacy Policy</a> | <a class="hover:text-primary transition-colors" href="#">Terms of Service</a>
</footer>
</div>
</div>
</div>
