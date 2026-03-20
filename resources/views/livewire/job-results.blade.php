<div wire:poll.5s>
<div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100">
<div class="layout-container flex h-full grow flex-col">
<main class="flex flex-1 justify-center py-8 px-4 md:px-8">
<div class="layout-content-container flex flex-col w-full gap-6">
    <!-- Page Title & Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex flex-col gap-1">
            <a href="{{ route('result') }}" class="text-primary text-sm font-semibold hover:underline inline-flex items-center gap-1 mb-1">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Back to job list
            </a>
            <h1 class="text-slate-900 dark:text-white text-3xl font-black leading-tight tracking-[-0.033em]">Results for Job #{{ $job->id }}</h1>
            <p class="text-slate-500 dark:text-slate-400 text-base font-normal">Manage and export business leads for this search.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="exportCsv" class="flex items-center justify-center rounded-lg h-10 px-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-all">
                <span class="material-symbols-outlined text-sm mr-2">description</span>
                Export CSV
            </button>
            <button wire:click="exportExcel" class="flex items-center justify-center rounded-lg h-10 px-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-all">
                <span class="material-symbols-outlined text-sm mr-2">table_chart</span>
                Export Excel
            </button>
            <button type="button" wire:click="rerun" wire:loading.attr="disabled" class="flex items-center justify-center rounded-lg h-10 px-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-all">
                <span class="material-symbols-outlined text-sm mr-2" wire:loading.remove wire:target="rerun">replay</span>
                <span class="material-symbols-outlined text-sm mr-2 animate-spin" wire:loading wire:target="rerun">sync</span>
                Rerun job
            </button>
            <a href="{{ route('search') }}" class="flex items-center justify-center rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold hover:opacity-90 transition-all shadow-sm">
                <span class="material-symbols-outlined text-sm mr-2">add</span>
                Start New Search
            </a>
        </div>
    </div>

    <!-- Summary Bar Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="flex flex-col gap-1 rounded-xl p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">Keyword</p>
            <p class="text-slate-900 dark:text-white text-xl font-bold">{{ $job->keyword }}</p>
        </div>
        <div class="flex flex-col gap-1 rounded-xl p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">Location</p>
            <p class="text-slate-900 dark:text-white text-xl font-bold">{{ $job->location }}</p>
        </div>
        <div class="flex flex-col gap-1 rounded-xl p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">Source</p>
            <p class="text-slate-900 dark:text-white text-xl font-bold">{{ ucfirst($job->source ?? '—') }}</p>
        </div>
        <div class="flex flex-col gap-1 rounded-xl p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">Results Found</p>
            <p class="text-slate-900 dark:text-white text-xl font-bold">{{ $totalResults }}</p>
        </div>
        <div class="flex flex-col gap-1 rounded-xl p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">Status</p>
            <div class="flex items-center gap-2">
                @php
                    $status = $job->status;
                    $statusColor = match($status) {
                        'completed' => 'text-emerald-600 dark:text-emerald-400',
                        'failed' => 'text-red-600 dark:text-red-400',
                        'running' => 'text-blue-600 dark:text-blue-400',
                        default => 'text-slate-600 dark:text-slate-400',
                    };
                    $icon = match($status) {
                        'completed' => 'check',
                        'failed' => 'close',
                        'running' => 'sync',
                        default => 'more_horiz',
                    };
                    $iconBg = match($status) {
                        'completed' => 'bg-emerald-100 dark:bg-emerald-900/30',
                        'failed' => 'bg-red-100 dark:bg-red-900/30',
                        'running' => 'bg-blue-100 dark:bg-blue-900/30',
                        default => 'bg-slate-100 dark:bg-slate-900/30',
                    };
                @endphp
                <span class="{{ $statusColor }} text-xl font-bold">{{ ucfirst($status) }}</span>
                <span class="flex size-5 items-center justify-center rounded-full {{ $iconBg }} {{ $statusColor }}">
                    <span class="material-symbols-outlined text-xs font-bold {{ $status === 'running' ? 'animate-spin' : '' }}">{{ $icon }}</span>
                </span>
            </div>
        </div>
    </div>

    @if ($job->status === 'failed' && $job->error_message)
        <div class="rounded-xl border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-950/30 p-4">
            <p class="text-sm font-semibold text-red-800 dark:text-red-300 mb-1">Why this job failed</p>
            <p class="text-sm text-red-700 dark:text-red-400">{{ $job->error_message }}</p>
        </div>
    @endif

    <!-- Results Table Section -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800">
            <label class="flex flex-col min-w-40 h-10 w-full md:max-w-md">
                <div class="flex w-full flex-1 items-stretch rounded-lg h-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    <div class="text-slate-500 dark:text-slate-400 flex items-center justify-center pl-3">
                        <span class="material-symbols-outlined text-lg">search</span>
                    </div>
                    <input wire:model.live="search" class="form-input flex w-full min-w-0 flex-1 border-none bg-transparent focus:outline-0 focus:ring-0 text-slate-900 dark:text-white placeholder:text-slate-500 dark:placeholder:text-slate-400 px-3 text-sm font-normal" placeholder="Filter results by name, email or category..."/>
                </div>
            </label>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Business Name</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Address</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Phone</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Website</th>
                        <th class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($results as $result)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-4 text-slate-900 dark:text-white font-medium text-sm">{{ $result->name }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-400 text-sm">{{ $result->category }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-400 text-sm">{{ $result->address }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-400 text-sm">
                                @if (!empty(trim((string) $result->phone)))
                                    {{ $result->phone }}
                                @else
                                    <span class="text-slate-400 dark:text-slate-600 italic">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm">
                                @php
                                    $displayEmail = $result->email ?? $result->businessEmails->first()?->email;
                                @endphp
                                @if ($displayEmail)
                                    <a href="mailto:{{ $displayEmail }}" class="text-primary font-medium underline decoration-primary/30">{{ $displayEmail }}</a>
                                @else
                                    <span class="text-slate-400 dark:text-slate-600 italic">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm">
                                @if (!empty(trim((string) $result->website)))
                                    <a href="{{ str_starts_with($result->website, 'http') ? $result->website : 'https://' . $result->website }}" 
                                       target="_blank" 
                                       rel="noopener noreferrer" 
                                       class="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg bg-primary/10 text-primary text-xs font-bold hover:bg-primary hover:text-white transition-all shadow-sm border border-primary/20">
                                        <span>Visit Site</span>
                                        <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 dark:text-slate-600 italic">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="viewDetails({{ $result->id }})" class="flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg bg-primary text-white text-sm font-semibold hover:opacity-90 transition-all shadow-sm" title="View Details">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        <span>View</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-400 dark:text-slate-600 text-sm">No results found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
            <p class="text-slate-500 dark:text-slate-400 text-sm">
                Showing {{ $totalResults > 0 ? ($currentPage - 1) * $perPage + 1 : 0 }} to {{ min($currentPage * $perPage, $totalResults) }} of {{ $totalResults }} leads
            </p>
            <div class="flex gap-1">
                <button wire:click="previousPage" @disabled($currentPage === 1) class="size-8 flex items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                @for ($i = 1; $i <= $totalPages; $i++)
                    @if ($i === 1 || $i === $totalPages || abs($i - $currentPage) <= 1)
                        <button wire:click="goToPage({{ $i }})" class="size-8 flex items-center justify-center rounded-lg border text-sm font-bold transition-colors {{ $currentPage === $i ? 'border-primary bg-primary text-white' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                            {{ $i }}
                        </button>
                    @elseif (abs($i - $currentPage) === 2)
                        <span class="px-1 py-1 text-slate-400 dark:text-slate-600">...</span>
                    @endif
                @endfor
                <button wire:click="nextPage" @disabled($currentPage === $totalPages) class="size-8 flex items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</div>
</main>
<footer class="mt-auto py-8 px-8 border-t border-slate-200 dark:border-slate-800 text-center text-slate-400 dark:text-slate-600 text-xs">
    &copy; 2024 LeadScraper Pro. All rights reserved. | <a class="hover:text-primary transition-colors" href="#">Privacy Policy</a> | <a class="hover:text-primary transition-colors" href="#">Terms of Service</a>
</footer>
</div>
</div>
</div>
