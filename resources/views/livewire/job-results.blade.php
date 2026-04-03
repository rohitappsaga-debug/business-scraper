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
            <h1 class="text-slate-900 dark:text-white text-3xl font-black leading-tight tracking-[-0.033em]">Results for Job #{{ $this->job->id }}</h1>
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
            <p class="text-slate-900 dark:text-white text-xl font-bold">{{ $this->job->keyword }}</p>
        </div>
        <div class="flex flex-col gap-1 rounded-xl p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">Location</p>
            <p class="text-slate-900 dark:text-white text-xl font-bold">{{ $this->job->location }}</p>
        </div>
        <div class="flex flex-col gap-1 rounded-xl p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">Source</p>
            <p class="text-slate-900 dark:text-white text-xl font-bold">{{ ucfirst($this->job->source ?? '—') }}</p>
        </div>
        <div class="flex flex-col gap-1 rounded-xl p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">Results Found</p>
            <p class="text-slate-900 dark:text-white text-xl font-bold">{{ $totalResults }}</p>
        </div>
        <div class="flex flex-col gap-1 rounded-xl p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">Status</p>
            <div class="flex items-center gap-2">
                @php
                    $status = $this->job->status;
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

    @if ($this->job->status === 'failed' && $this->job->error_message)
        <div class="rounded-xl border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-950/30 p-4">
            <p class="text-sm font-semibold text-red-800 dark:text-red-300 mb-1">Why this job failed</p>
            <p class="text-sm text-red-700 dark:text-red-400">{{ $this->job->error_message }}</p>
        </div>
    @endif

    <!-- ⚡ Deep Data Enrichment Loader (Professional & Compact) -->
    @if ($this->isEnriching)
        <div class="relative overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm px-5 py-4 bg-white dark:bg-slate-800 transition-all mb-4">
            
            <style>
                @keyframes slideIndeterminate {
                    0% { transform: translateX(-100%); }
                    100% { transform: translateX(300%); }
                }
                .indeterminate-progress {
                    animation: slideIndeterminate 1.5s infinite ease-in-out;
                }
            </style>

            <div class="relative z-10 flex items-center gap-4">
                <!-- Classic Professional Spinner -->
                <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-blue-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>

                <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-3 flex-1 min-w-0">
                    <h3 class="text-slate-900 dark:text-white text-sm font-semibold tracking-tight shrink-0">
                        Extracting Deep Data & Social Links...
                    </h3>
                    <p class="text-slate-500 dark:text-slate-400 text-xs truncate">
                        The primary search is complete. Background workers are dynamically visiting business websites right now to accurately locate their emails and social media accounts.
                    </p>
                </div>
            </div>
            
            <!-- Classic Indeterminate Bottom Bar -->
            <div class="absolute" style="bottom: 0; left: 0; width: 100%; height: 2px; background-color: rgba(148, 163, 184, 0.2);">
                <div class="indeterminate-progress" style="height: 100%; width: 33.333333%; background-color: #2563eb; border-radius: 9999px;"></div>
            </div>
        </div>
    @endif

    <!-- ⚡ Premium Live Activity Loader -->
    @if ($this->job->status === 'running')
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-blue-200 dark:border-blue-900/30 shadow-lg p-6 group transition-all">
            <!-- Animated Background Glow -->
            <div class="absolute -top-24 -left-24 size-64 bg-blue-500/10 blur-[80px] pointer-events-none transition-all group-hover:bg-blue-500/20"></div>
            
            <div class="relative flex flex-col md:flex-row items-center gap-6">
                <!-- Circular Pulse Loader -->
                <div class="relative flex items-center justify-center shrink-0">
                    <div class="absolute size-20 rounded-full border-2 border-blue-500/20 animate-[ping_2s_infinite]"></div>
                    <div class="absolute size-16 rounded-full border-4 border-blue-500/40 animate-[spin_3s_linear_infinite]"></div>
                    <div class="z-10 flex size-12 items-center justify-center rounded-full bg-blue-600 text-white shadow-xl shadow-blue-500/20">
                        <span class="material-symbols-outlined animate-pulse">radar</span>
                    </div>
                </div>

                <!-- Status Info -->
                <div class="flex-1 text-center md:text-left">
                    <div class="flex items-center justify-center md:justify-start gap-2 mb-1">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                        </span>
                        <h3 class="text-slate-900 dark:text-white text-lg font-black tracking-tight">Active Lead Discovery In Progress</h3>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium flex items-center justify-center md:justify-start gap-2">
                        <span class="material-symbols-outlined text-sm text-blue-500">location_on</span>
                        @if($this->job->current_location)
                            Exploring: <span class="text-blue-600 dark:text-blue-400 font-bold decoration-blue-500/30 underline underline-offset-4">{{ $this->job->current_location }}</span>
                        @else
                            Initializing geographic expansion and cleaning local buffers...
                        @endif
                    </p>
                </div>

                <!-- Metrics/Activity -->
                <div class="flex flex-col items-center md:items-end gap-1 px-6 border-l border-slate-100 dark:border-slate-800 hidden lg:flex">
                    <div class="text-blue-600 dark:text-blue-400 text-2xl font-black">{{ $totalResults }}</div>
                    <div class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Leads Captured So Far</div>
                </div>
            </div>
            
            <!-- Bottom Progress Line (Decorative) -->
            <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-blue-600 via-sky-400 to-transparent w-full animate-[shimmer_2s_infinite]"></div>
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 md:p-6 mb-2">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <!-- Global Search -->
            <div class="flex flex-col gap-1.5 lg:col-span-2">
                <label for="search" class="text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Search</label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-[20px] group-focus-within:text-primary transition-colors">search</span>
                    <input 
                        type="text" 
                        id="search" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name, email or category..." 
                        class="w-full h-11 pl-10 pr-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600"
                    >
                </div>
            </div>

            <!-- City Filter -->
            <div class="flex flex-col gap-1.5">
                <label for="city" class="text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">City</label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-[20px] group-focus-within:text-primary transition-colors">location_city</span>
                    <input 
                        type="text" 
                        id="city" 
                        wire:model.live.debounce.300ms="city"
                        placeholder="e.g. New York..." 
                        class="w-full h-11 pl-10 pr-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600"
                    >
                </div>
            </div>

            <!-- State Filter -->
            <div class="flex flex-col gap-1.5">
                <label for="state" class="text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">State</label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-[20px] group-focus-within:text-primary transition-colors">map</span>
                    <input 
                        type="text" 
                        id="state" 
                        wire:model.live.debounce.300ms="state"
                        placeholder="e.g. NY..." 
                        class="w-full h-11 pl-10 pr-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600"
                    >
                </div>
            </div>

            <!-- Country Filter -->
            <div class="flex flex-col gap-1.5">
                <label for="country" class="text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Country</label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-[20px] group-focus-within:text-primary transition-colors">public</span>
                    <input 
                        type="text" 
                        id="country" 
                        wire:model.live.debounce.300ms="country"
                        placeholder="e.g. USA..." 
                        class="w-full h-11 pl-10 pr-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600"
                    >
                </div>
            </div>

            <!-- Reset Filters -->
            @if($this->hasActiveFilters)
                <div class="flex lg:col-span-5 md:col-span-2 justify-end">
                    <button 
                        type="button" 
                        wire:click="resetFilters"
                        class="flex items-center justify-center gap-2 h-11 px-6 w-full sm:w-auto rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm"
                    >
                        <span class="material-symbols-outlined text-[18px]">filter_alt_off</span>
                        Clear Filters
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Results Table Section -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
        <div class="flex flex-col md:flex-row items-center justify-end gap-4 p-4 border-b border-slate-100 dark:border-slate-800">
            <!-- Rows Selector -->
            <div class="flex items-center gap-3 shrink-0 group/row-select">
                <span class="text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest leading-none">Show</span>
                <div class="relative flex items-center">
                    <select wire:model.live="perPage" 
                        class="h-9 w-20 pl-3 pr-8 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-black focus:outline-0 focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer outline-none text-center appearance-none"
                        style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-2 text-slate-400 pointer-events-none text-[20px] transition-colors group-hover/row-select:text-primary uppercase tracking-none">unfold_more</span>
                </div>
                <span class="text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest leading-none">Rows</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Business Name</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Address</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Phone</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider text-center">Socials</th>
                        <th class="px-4 py-3 text-slate-900 dark:text-slate-200 text-xs font-bold uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($results as $result)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-4 text-slate-900 dark:text-white font-medium text-sm">{{ $result->name }}</td>
                            <td class="px-4 py-4 text-slate-900 dark:text-slate-200 font-medium text-sm">{{ $result->category }}</td>
                            <td class="px-4 py-4 text-slate-900 dark:text-slate-200 font-medium text-sm">{{ $result->address }}</td>
                            <td class="px-4 py-4 text-slate-900 dark:text-slate-200 font-medium text-sm">
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
                                    @php $socialCount = 0; @endphp
                                    @foreach(['instagram', 'facebook', 'linkedin', 'youtube', 'twitter', 'x'] as $platform)
                                        @php 
                                            $link = $result->socialLinks->where('platform', $platform)->first(); 
                                        @endphp
                                        @if($link)
                                            @php $socialCount++; @endphp
                                            <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="text-slate-800 dark:text-slate-300 hover:text-primary transition-colors" title="{{ ucfirst($platform) }}">
                                                @if($platform === 'facebook')
                                                    <svg class="size-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                                @elseif($platform === 'instagram')
                                                    <svg class="size-4 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                                @elseif($platform === 'twitter' || $platform === 'x')
                                                    <svg class="size-4 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                                @elseif($platform === 'linkedin')
                                                    <svg class="size-4 fill-current" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                                @elseif($platform === 'youtube')
                                                    <svg class="size-4 fill-current" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                                                @endif
                                            </a>
                                        @endif
                                    @endforeach
                                    @if($socialCount === 0)
                                        <span class="text-slate-400 dark:text-slate-600 italic">—</span>
                                    @endif
                                </div>
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
                            <td colspan="8" class="px-4 py-16 text-center">
                                @if($this->job->status === 'running')
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="size-10 rounded-full border-4 border-slate-200 dark:border-slate-800 border-t-primary animate-spin"></div>
                                        <div class="flex flex-col gap-1">
                                            <p class="text-slate-900 dark:text-white text-base font-bold tracking-tight">Initializing Discovery Engine...</p>
                                            <p class="text-slate-500 dark:text-slate-400 text-sm">We are expanding coordinates and initializing target city buffers. Results will stream in shortly.</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-slate-300 dark:text-slate-700 text-4xl">folder_off</span>
                                        <p class="text-slate-400 dark:text-slate-600 text-sm font-medium">No results found for this search criteria.</p>
                                    </div>
                                @endif
                            </td>
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
    &copy; 2026 LeadScraper Pro. All rights reserved. | <a class="hover:text-primary transition-colors" href="#">Privacy Policy</a> | <a class="hover:text-primary transition-colors" href="#">Terms of Service</a>
</footer>
</div>
</div>
</div>
