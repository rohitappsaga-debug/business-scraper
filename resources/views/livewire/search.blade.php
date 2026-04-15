<div>
    <style>
        /* ── Photon Autocomplete ───────────────────────── */
        .pa-wrapper {
            position: relative;
        }

        /* Light mode defaults */
        .pa-input {
            width: 100%;
            padding: 12px 16px 12px 42px;
            background: #f8fafc;       /* slate-50 */
            border: 1px solid #e2e8f0; /* slate-200 */
            border-radius: 8px;
            color: #0f172a;            /* slate-900 */
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease, border-radius 0.15s ease, box-shadow 0.2s ease;
        }

        .pa-input::placeholder {
            color: #94a3b8; /* slate-400 */
        }

        .pa-input:focus {
            border-color: var(--color-primary, #6366f1);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary, #6366f1) 20%, transparent);
        }

        .pa-input.is-open {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            border-bottom-color: #e2e8f0;
            box-shadow: none;
        }

        .pa-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8; /* slate-400 */
            font-size: 18px;
            pointer-events: none;
            transition: color 0.2s ease;
            z-index: 1;
        }

        .pa-spinner {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
        }

        .pa-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 9999;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            overflow-x: hidden;
            max-height: 240px;
            list-style: none;
            margin: 0;
            padding: 4px 0;
        }

        .pa-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            cursor: pointer;
            transition: all 0.15s ease;
            border-left: 2px solid transparent;
        }

        .pa-item:hover,
        .pa-item.is-highlighted {
            background: #f1f5f9; /* slate-100 */
            border-left-color: var(--color-primary, #6366f1);
        }

        .pa-item-icon {
            color: #cbd5e1; /* slate-300 */
            font-size: 18px;
            flex-shrink: 0;
            transition: color 0.15s ease;
        }

        .pa-item:hover .pa-item-icon,
        .pa-item.is-highlighted .pa-item-icon {
            color: var(--color-primary, #6366f1);
        }

        .pa-item-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .pa-item-title {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b; /* slate-800 */
            transition: color 0.15s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pa-item.is-highlighted .pa-item-title {
            color: var(--color-primary, #6366f1);
        }

        .pa-item-subtitle {
            font-size: 11px;
            color: #94a3b8; /* slate-400 */
            margin-top: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pa-no-results {
            padding: 14px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }

        /* ── Dark mode overrides ───────────────────────── */
        .dark .pa-input {
            background: #1e293b;       /* slate-800 */
            border-color: #334155;     /* slate-700 */
            color: #e2e8f0;
        }

        .dark .pa-input::placeholder {
            color: #475569;
        }

        .dark .pa-input.is-open {
            border-bottom-color: #334155;
        }

        .dark .pa-icon {
            color: #475569;
        }

        .dark .pa-dropdown {
            background: #0b1220;
            border-color: #1f2a44;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }

        .dark .pa-item:hover,
        .dark .pa-item.is-highlighted {
            background: #1e293b;
        }

        .dark .pa-item-icon {
            color: #334155;
        }

        .dark .pa-item-title {
            color: #e2e8f0;
        }

        .dark .pa-item.is-highlighted .pa-item-title {
            color: #93c5fd;
        }

        .dark .pa-item-subtitle {
            color: #64748b;
        }

        .dark .pa-no-results {
            color: #4a5568;
        }

        /* Scrollbar */
        .pa-dropdown::-webkit-scrollbar { width: 4px; }
        .pa-dropdown::-webkit-scrollbar-track { background: transparent; }
        .pa-dropdown::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .dark .pa-dropdown::-webkit-scrollbar-thumb { background: #1f2a44; }
        .pa-dropdown { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
        .dark .pa-dropdown { scrollbar-color: #1f2a44 transparent; }
    </style>
    <div class="relative flex h-screen w-full overflow-hidden bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100">
        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col overflow-y-auto w-full items-center justify-center min-h-screen">
            <!-- Page Content -->
            <div class="max-w-4xl mx-auto w-full px-8 py-8">
                <div class="mb-8 flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Create New Scraping Job</h1>
                        <p class="text-slate-500 dark:text-slate-400">Configure your parameters to start extracting data from the web in real-time.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('settings') }}" wire:navigate class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-primary transition-all shadow-sm flex items-center justify-center group" title="Account Settings">
                            <span class="material-symbols-outlined group-hover:rotate-90 transition-transform duration-500">settings</span>
                        </a>
                        <a href="{{ route('logout') }}" @click.prevent="$wire.confirmLogout()" class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shadow-sm flex items-center justify-center group" title="Logout">
                            <span class="material-symbols-outlined group-hover:scale-110 transition-transform pointer-events-none">logout</span>
                        </a>
                    </div>
                </div>

                @if (session()->has('success'))
                    <div class="mb-4 p-4 text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800" role="alert">
                        <span class="font-medium">Success!</span> {{ session('success') }}
                    </div>
                @endif

                <!-- Form Card -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="p-1 bg-gradient-to-r from-primary/50 to-primary/10"></div>
                    <div class="p-8">
                        <form wire:submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Keyword Input -->
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Keyword / Profession</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">work</span>
                                        <input wire:model="keyword" class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all" placeholder="e.g. Plumbers, Dentists" type="text" required/>
                                    </div>
                                    @error('keyword') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <!-- Location Input -->
                                <div class="flex flex-col gap-2" x-data="locationAutocomplete(@entangle('location'))" @click.outside="close()">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Location / City</label>
                                    <div class="pa-wrapper">

                                        <!-- Icon -->
                                        <span class="material-symbols-outlined pa-icon" style="z-index:1;">location_on</span>

                                        <!-- Input -->
                                        <input
                                            x-model="query"
                                            @input.debounce.300ms="fetchSuggestions"
                                            @keydown.down.prevent="highlightNext"
                                            @keydown.up.prevent="highlightPrevious"
                                            @keydown.enter.prevent="selectHighlighted"
                                            @focus="open = suggestions.length > 0"
                                            :class="open ? 'pa-input is-open' : 'pa-input'"
                                            placeholder="e.g. London, New York"
                                            type="text"
                                            autocomplete="off"
                                            required
                                        />

                                        <!-- Loading Spinner -->
                                        <div x-show="loading" class="pa-spinner" style="display:none;">
                                            <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                        </div>

                                        <!-- Dropdown -->
                                        <ul
                                            x-show="open"
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 -translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 -translate-y-1"
                                            class="pa-dropdown"
                                            style="display:none;"
                                        >
                                            <template x-for="(item, index) in suggestions" :key="index">
                                                <li
                                                    @click="selectLocation(item)"
                                                    @mouseenter="highlightedIndex = index"
                                                    :id="'suggestion-' + index"
                                                    :class="highlightedIndex === index ? 'pa-item is-highlighted' : 'pa-item'"
                                                >
                                                    <span class="material-symbols-outlined pa-item-icon">place</span>
                                                    <div class="pa-item-body">
                                                        <span class="pa-item-title" x-text="item.properties.name || item.properties.city || item.properties.state"></span>
                                                        <span class="pa-item-subtitle" x-text="formatLocationDetails(item)"></span>
                                                    </div>
                                                </li>
                                            </template>

                                            <li x-show="suggestions.length === 0 && !loading && query.length >= 3" class="pa-no-results">
                                                No results found.
                                            </li>
                                        </ul>
                                    </div>
                                    @error('location') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Search Depth -->
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Search Limit (Records)</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">list_alt</span>
                                        <input wire:model="limit" class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all disabled:opacity-50 disabled:cursor-not-allowed" min="1" step="1" type="number" required @if($isUnlimited) disabled @endif/>
                                    </div>
                                    @error('limit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Unlimited Option (Fills the empty right column) -->
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-transparent select-none hidden md:block" aria-hidden="true">Unlimited</label>
                                    <label for="unlimited" class="flex items-center justify-between px-4 py-3 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 rounded-lg cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors w-full">
                                        <div class="flex items-center gap-3">
                                            <span class="material-symbols-outlined text-primary">all_inclusive</span>
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Scrape Maximum Data</span>
                                        </div>
                                        <input type="checkbox" id="unlimited" wire:model.live="isUnlimited" class="w-5 h-5 text-primary bg-white border-slate-300 rounded focus:ring-primary focus:ring-2 dark:bg-slate-900 dark:border-slate-600 shadow-sm cursor-pointer">
                                    </label>
                                </div>

                            </div>
                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-4 pt-4">
                                <button class="bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-2 w-full" type="submit">
                                    <span class="material-symbols-outlined">rocket_launch</span>
                                    <span wire:loading.remove wire:target="submit">Start Scraping Job</span>
                                    <span wire:loading wire:target="submit">Processing...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Footer Info -->
                <div class="mt-8 flex items-center justify-center text-slate-500 dark:text-slate-600 text-xs">
                    <span>Made with ❤️ by <span class="font-semibold text-slate-400 dark:text-slate-500">AppSaga</span></span>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('locationAutocomplete', (entangledLocation) => ({
                query: entangledLocation,
                suggestions: [],
                open: false,
                loading: false,
                highlightedIndex: -1,
                
                async fetchSuggestions() {
                    if (this.query.length < 3) {
                        this.suggestions = [];
                        this.open = false;
                        return;
                    }

                    this.loading = true;
                    try {
                        const response = await fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(this.query)}&limit=15`);
                        if (!response.ok) throw new Error('API fetch failed');
                        const data = await response.json();

                        const seen = new Set();
                        const unique = [];
                        for (const place of (data.features || [])) {
                            const name    = (place.properties.name    || '').trim();
                            const city    = (place.properties.city    || '').trim();
                            const state   = (place.properties.state   || '').trim();
                            const country = (place.properties.country || '').trim();
                            const key = `${name}|${city}|${state}|${country}`.toLowerCase();
                            if (!seen.has(key)) {
                                seen.add(key);
                                unique.push(place);
                            }
                            if (unique.length === 5) break;
                        }

                        this.suggestions = unique;
                        this.open = unique.length > 0;
                        this.highlightedIndex = -1;
                    } catch (error) {
                        console.error('Photon API Error:', error);
                        this.suggestions = [];
                    } finally {
                        this.loading = false;
                    }
                },

                formatLocationDetails(feature) {
                    const parts = [];
                    if (feature.properties.city && feature.properties.name !== feature.properties.city) {
                        parts.push(feature.properties.city);
                    }
                    if (feature.properties.state && feature.properties.name !== feature.properties.state && feature.properties.city !== feature.properties.state) {
                        parts.push(feature.properties.state);
                    }
                    if (feature.properties.country) {
                        parts.push(feature.properties.country);
                    }
                    return parts.join(', ');
                },

                selectLocation(feature) {
                    const name = feature.properties.name || '';
                    const city = feature.properties.city || feature.properties.state || '';
                    const country = feature.properties.country || '';
                    
                    const locationString = [name, city, country].filter(Boolean).filter((v, i, a) => a.indexOf(v) === i).join(', ');
                    
                    this.query = locationString;
                    this.close();
                },

                highlightNext() {
                    if (!this.open || this.suggestions.length === 0) return;
                    this.highlightedIndex = (this.highlightedIndex + 1) % this.suggestions.length;
                    this.scrollToHighlighted();
                },

                highlightPrevious() {
                    if (!this.open || this.suggestions.length === 0) return;
                    this.highlightedIndex = this.highlightedIndex - 1;
                    if (this.highlightedIndex < 0) {
                        this.highlightedIndex = this.suggestions.length - 1;
                    }
                    this.scrollToHighlighted();
                },

                scrollToHighlighted() {
                    this.$nextTick(() => {
                        const el = document.getElementById('suggestion-' + this.highlightedIndex);
                        if (el) {
                            el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                        }
                    });
                },

                selectHighlighted() {
                    if (this.open && this.highlightedIndex >= 0 && this.highlightedIndex < this.suggestions.length) {
                        this.selectLocation(this.suggestions[this.highlightedIndex]);
                    } else if (this.query.length > 0) {
                        // Allow pressing enter to submit the form if nothing is highlighted
                        // Or simply close if we treat enter as a selection
                        this.close();
                    }
                },

                close() {
                    this.open = false;
                    this.highlightedIndex = -1;
                }
            }));
        });
    </script>
</div>
