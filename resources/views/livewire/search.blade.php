<div>
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
                    <a href="{{ route('settings') }}" wire:navigate class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-primary transition-all shadow-sm flex items-center justify-center group" title="Account Settings">
                        <span class="material-symbols-outlined group-hover:rotate-90 transition-transform duration-500">settings</span>
                    </a>
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
                        <form wire:submit="submit" class="space-y-6">
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
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Location / City</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">location_on</span>
                                        <input wire:model="location" class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all" placeholder="e.g. London, New York" type="text" required/>
                                    </div>
                                    @error('location') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <!-- Search Depth -->
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Search Limit (Records)</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">list_alt</span>
                                        <input wire:model="limit" class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all" min="1" step="1" type="number" required/>
                                    </div>
                                    @error('limit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
                <div class="mt-8 flex items-center justify-center gap-6 text-slate-400 dark:text-slate-600 text-xs">
                    <div class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">bolt</span>
                        <span>Estimated processing time: 5-10 mins</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">security</span>
                        <span>Anti-bot protection active</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">info</span>
                        <span>Usage limit: 12/50 active jobs</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
