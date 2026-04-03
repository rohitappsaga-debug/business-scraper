<div wire:poll.5s>
<div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100">
<div class="layout-container flex h-full grow flex-col">
<main class="flex flex-1 justify-center py-8 px-4 md:px-8">
<div class="layout-content-container flex flex-col w-full gap-6">
    <!-- Page Title & Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex flex-col gap-1">
            <h1 class="text-slate-900 dark:text-white text-3xl font-black leading-tight tracking-[-0.033em]">Scraping Jobs</h1>
            <p class="text-slate-800 dark:text-slate-300 text-base font-medium">View and manage your scraping jobs. Click a job to see its results.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('search') }}" class="flex items-center justify-center rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold hover:opacity-90 transition-all shadow-sm">
                <span class="material-symbols-outlined text-sm mr-2">add</span>
                Start New Search
            </a>
            <a href="{{ route('logout') }}" @click.prevent="$wire.confirmLogout()" class="flex items-center justify-center rounded-lg h-10 px-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-red-500 text-sm font-bold hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shadow-sm cursor-pointer" title="Logout">
                <span class="material-symbols-outlined text-[18px] mr-2 pointer-events-none">logout</span>
                Logout
            </a>
        </div>
    </div>

    @if (session('message'))
        <div class="rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-emerald-800 dark:text-emerald-200 text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 md:p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
            <!-- Keyword Filter -->
            <div class="flex flex-col gap-1.5">
                <label for="keyword" class="text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Keyword</label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-[20px] group-focus-within:text-primary transition-colors">search</span>
                    <input 
                        type="text" 
                        id="keyword" 
                        wire:model.live.debounce.300ms="keyword"
                        placeholder="Search keywords..." 
                        class="w-full h-11 pl-10 pr-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600"
                    >
                </div>
            </div>

            <!-- Location Filter -->
            <div class="flex flex-col gap-1.5">
                <label for="location" class="text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Location</label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-[20px] group-focus-within:text-primary transition-colors">location_on</span>
                    <input 
                        type="text" 
                        id="location" 
                        wire:model.live.debounce.300ms="location"
                        placeholder="Filter by location..." 
                        class="w-full h-11 pl-10 pr-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600"
                    >
                </div>
            </div>

            <!-- Category Filter -->
            <div class="flex flex-col gap-1.5">
                <label for="category" class="text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Category</label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-[20px] group-focus-within:text-primary transition-colors">category</span>
                    <input 
                        type="text" 
                        id="category" 
                        wire:model.live.debounce.300ms="category"
                        placeholder="Filter by business category..." 
                        class="w-full h-11 pl-10 pr-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600"
                    >
                </div>
            </div>

            <!-- Reset Filters -->
            @if($this->hasActiveFilters)
                <div class="flex">
                    <button 
                        type="button" 
                        wire:click="resetFilters"
                        class="flex items-center justify-center gap-2 h-11 px-6 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm"
                    >
                        <span class="material-symbols-outlined text-[18px]">filter_alt_off</span>
                        Clear Filters
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Jobs Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Job #</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Keyword</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Source</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Results</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Created</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider text-center">Actions</th>
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
                                'cancelled' => 'text-amber-600 dark:text-amber-400',
                                default => 'text-slate-600 dark:text-slate-400',
                            };
                            $iconBg = match($status) {
                                'completed' => 'bg-emerald-100 dark:bg-emerald-900/30',
                                'failed' => 'bg-red-100 dark:bg-red-900/30',
                                'running' => 'bg-blue-100 dark:bg-blue-900/30',
                                'cancelled' => 'bg-amber-100 dark:bg-amber-900/30',
                                default => 'bg-slate-100 dark:bg-slate-900/30',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-4 text-slate-900 dark:text-white font-bold text-sm">{{ $job->id }}</td>
                            <td class="px-4 py-4 text-slate-900 dark:text-white font-medium text-sm">{{ $job->keyword }}</td>
                            <td class="px-4 py-4 text-slate-900 dark:text-slate-200 font-medium text-sm">{{ $job->location }}</td>
                            <td class="px-4 py-4 text-slate-900 dark:text-slate-200 font-medium text-sm">{{ ucfirst($job->source ?? '—') }}</td>
                            <td class="px-4 py-4">
                                <span class="flex items-center gap-1.5 w-fit rounded-full px-2.5 py-0.5 {{ $iconBg }} {{ $statusColor }} text-xs font-semibold">
                                    @if ($status === 'running')
                                        <span class="material-symbols-outlined text-xs animate-spin">sync</span>
                                    @endif
                                    {{ ucfirst($status) }}
                                </span>
                                @if($status === 'running' && $job->current_location)
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 animate-pulse flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[10px]">near_me</span>
                                        Exploring: {{ $job->current_location }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-slate-900 dark:text-slate-200 font-medium text-sm">{{ $job->results_count ?? 0 }}</td>
                            <td class="px-4 py-4 text-slate-900 dark:text-slate-200 font-medium text-sm">{{ $job->created_at?->format('M j, Y g:i A') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <a href="{{ route('result.job', ['id' => $job->id]) }}" class="flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg bg-primary text-white text-sm font-semibold hover:opacity-90 transition-all shadow-sm" title="View results">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        <span>View results</span>
                                    </a>
                                    @if (in_array($status, ['pending', 'running']))
                                        <button type="button" @click="window.dispatchEvent(new CustomEvent('open-confirm-modal', { 
                                            detail: { 
                                                title: 'Cancel Job', 
                                                message: 'Are you sure you want to cancel this scraping job?', 
                                                confirmButton: 'Cancel Job',
                                                type: 'danger',
                                                confirmAction: { name: 'cancel-job', data: { jobId: {{ $job->id }} } }
                                            } 
                                        }))" wire:loading.attr="disabled" class="flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-sm font-semibold hover:bg-red-100 dark:hover:bg-red-900/40 transition-all" title="Cancel job">
                                            <span class="material-symbols-outlined text-[18px] pointer-events-none" wire:loading.remove wire:target="cancelJob({{ $job->id }})">cancel</span>
                                            <span class="material-symbols-outlined text-[18px] animate-spin pointer-events-none" wire:loading wire:target="cancelJob({{ $job->id }})">sync</span>
                                            <span class="pointer-events-none">Cancel</span>
                                        </button>
                                    @endif
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
                <p class="text-slate-800 dark:text-slate-300 text-sm font-medium">
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
<footer class="mt-auto py-8 px-8 border-t border-slate-200 dark:border-slate-800 text-center text-slate-600 dark:text-slate-400 text-xs font-medium">
    &copy; 2024 LeadScraper Pro. All rights reserved. | <a class="hover:text-primary transition-colors text-slate-800 dark:text-slate-300" href="#">Privacy Policy</a> | <a class="hover:text-primary transition-colors text-slate-800 dark:text-slate-300" href="#">Terms of Service</a>
</footer>
</div>
</div>
</div>
