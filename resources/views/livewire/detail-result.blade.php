<div>
    @if (session()->has('message'))
        <div class="max-w-7xl mx-auto w-full px-6 pt-4">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('message') }}
            </div>
        </div>
    @endif

    @if (empty($business))
        <main class="max-w-7xl mx-auto w-full px-6 pt-12 pb-8 text-center">
            <p class="text-slate-500 dark:text-slate-400">Business lead not found.</p>
            <button wire:click="backToResults" class="mt-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                Back to Results
            </button>
        </main>
    @else
        <main class="max-w-7xl mx-auto w-full px-6 pt-12 pb-8">
            {{-- Header --}}
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Business Lead Details</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">View complete information about the selected business lead.</p>
                </div>
                <div class="flex gap-3">
                    <button wire:click="backToResults" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Back to Results
                    </button>
                    <button wire:click="exportLead" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined">download</span>
                        Export Lead
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Left Column: Business Info --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Business Information Card --}}
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                            <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">domain</span>
                                Business Information
                            </h3>
                            <span class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full">
                                {{ $business['status'] ?? 'Active' }}
                            </span>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Business Name</label>
                                    <p class="text-slate-900 dark:text-white font-semibold">{{ $business['name'] }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Category</label>
                                    <p class="text-slate-900 dark:text-white font-semibold">{{ $business['category'] }}</p>
                                </div>
                                <div class="md:col-span-2 space-y-1">
                                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Description</label>
                                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">{{ $business['description'] }}</p>
                                </div>
                                <div class="md:col-span-2 space-y-1">
                                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Full Address</label>
                                    <p class="text-slate-900 dark:text-white font-semibold">{{ $business['address'] }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">City &amp; State</label>
                                    <p class="text-slate-900 dark:text-white font-semibold">{{ $business['city_state'] }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Postal &amp; Country</label>
                                    <p class="text-slate-900 dark:text-white font-semibold">{{ $business['postal_country'] }}</p>
                                </div>
                            </div>
                            {{-- Map Placeholder --}}
                            <div class="mt-8 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 aspect-[21/9] relative bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                <div class="text-center text-slate-400">
                                    <span class="material-symbols-outlined text-4xl mb-2 block">map</span>
                                    <p class="text-sm">Map view of {{ $business['name'] }}</p>
                                    <p class="text-xs mt-1">{{ $business['address'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Contact & Status --}}
                <div class="space-y-6">
                    {{-- Contact Details Card --}}
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">contact_page</span>
                                Contact Details
                            </h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="space-y-3">
                                {{-- Phone --}}
                                <div class="flex items-center justify-between group">
                                    <div class="flex items-center gap-3">
                                        <div class="size-9 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">call</span>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Phone</p>
                                            <p class="text-slate-900 dark:text-white font-semibold">{{ $business['phone'] ?: 'N/A' }}</p>
                                        </div>
                                    </div>
                                    @if(!empty($business['phone']))
                                        <button
                                            onclick="navigator.clipboard.writeText('{{ $business['phone'] }}')"
                                            class="p-2 text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-all"
                                            title="Copy Phone"
                                        >
                                            <span class="material-symbols-outlined">content_copy</span>
                                        </button>
                                    @endif
                                </div>

                                {{-- Email --}}
                                <div class="flex items-center justify-between group">
                                    <div class="flex items-center gap-3">
                                        <div class="size-9 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">mail</span>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Email</p>
                                            <p class="text-slate-900 dark:text-white font-semibold">{{ $business['email'] ?: 'N/A' }}</p>
                                        </div>
                                    </div>
                                    @if(!empty($business['email']))
                                        <button
                                            onclick="navigator.clipboard.writeText('{{ $business['email'] }}')"
                                            class="p-2 text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-all"
                                            title="Copy Email"
                                        >
                                            <span class="material-symbols-outlined">content_copy</span>
                                        </button>
                                    @endif
                                </div>

                                {{-- Website --}}
                                <div class="flex items-center justify-between group">
                                    <div class="flex items-center gap-3">
                                        <div class="size-9 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">language</span>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Website</p>
                                            <p class="text-slate-900 dark:text-white font-semibold truncate max-w-[140px]">{{ $business['website'] ?: 'N/A' }}</p>
                                        </div>
                                    </div>
                                    @if(!empty($business['website_url']))
                                        <a
                                            href="{{ $business['website_url'] }}"
                                            target="_blank"
                                            class="p-2 text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-all"
                                            title="Visit Website"
                                        >
                                            <span class="material-symbols-outlined">open_in_new</span>
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-2">
                                <button
                                    wire:click="openGoogleMaps"
                                    class="w-full bg-slate-100 dark:bg-slate-800 hover:bg-primary hover:text-white text-slate-700 dark:text-slate-300 px-4 py-2.5 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2"
                                >
                                    <span class="material-symbols-outlined">map</span>
                                    Open in Google Maps
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Lead Status Card --}}
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 space-y-4">
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm uppercase tracking-widest">Lead Status</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400 text-sm">Verification</span>
                                <div class="flex items-center gap-1.5 {{ $business['verification'] === 'Verified' ? 'text-green-600' : 'text-slate-400' }} font-bold text-sm">
                                    <span class="material-symbols-outlined text-[18px] fill-current">check_circle</span>
                                    {{ $business['verification'] }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400 text-sm">Email Status</span>
                                <div class="flex items-center gap-1.5 {{ $business['email_status'] === 'Found' ? 'text-blue-600' : 'text-slate-400' }} font-bold text-sm">
                                    <span class="material-symbols-outlined text-[18px]">contact_mail</span>
                                    {{ $business['email_status'] }}
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                                <div class="flex items-center gap-2 text-slate-400 text-xs">
                                    <span class="material-symbols-outlined text-[14px]">history</span>
                                    <span>Last Updated: {{ $business['last_updated'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Full Width: Additional Details --}}
            <div class="mt-6">
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">info</span>
                            Additional Details
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                            {{-- Business Hours --}}
                            <div class="space-y-3">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Business Hours</p>
                                <ul class="text-sm space-y-2">
                                    @foreach($business['hours'] as $hour)
                                        <li class="flex justify-between {{ $hour['time'] === 'Closed' ? 'text-slate-400' : 'text-slate-700 dark:text-slate-300' }}">
                                            <span>{{ $hour['day'] }}</span>
                                            <span class="font-medium">{{ $hour['time'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            {{-- Rating & Profile --}}
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Performance</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $business['rating'] }}</span>
                                        <div class="flex text-yellow-400">
                                            <span class="material-symbols-outlined fill-current">star</span>
                                            <span class="material-symbols-outlined fill-current">star</span>
                                            <span class="material-symbols-outlined fill-current">star</span>
                                            <span class="material-symbols-outlined fill-current">star</span>
                                            <span class="material-symbols-outlined">star_half</span>
                                        </div>
                                    </div>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">Based on {{ $business['review_count'] }} Google reviews</p>
                                </div>
                                @if(!empty($business['website_url']))
                                    <a href="{{ $business['website_url'] }}" target="_blank" class="inline-flex items-center gap-2 text-primary text-sm font-bold hover:underline">
                                        <span class="material-symbols-outlined">store</span>
                                        View Google Business Profile
                                    </a>
                                @endif
                            </div>

                            {{-- Social Media --}}
                            <div class="space-y-3">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Social Media</p>
                                @if(!empty($business['social']))
                                    <div class="flex gap-3">
                                        @if(!empty($business['social']['facebook']))
                                            <a href="{{ $business['social']['facebook'] }}" target="_blank" class="size-10 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-primary/10 hover:text-primary transition-all">
                                                <svg class="size-5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                            </a>
                                        @endif
                                        @if(!empty($business['social']['instagram']))
                                            <a href="{{ $business['social']['instagram'] }}" target="_blank" class="size-10 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-primary/10 hover:text-primary transition-all">
                                                <svg class="size-5 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                            </a>
                                        @endif
                                        @if(!empty($business['social']['youtube']))
                                            <a href="{{ $business['social']['youtube'] }}" target="_blank" class="size-10 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-primary/10 hover:text-primary transition-all">
                                                <svg class="size-5 fill-current" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-slate-400 text-sm">No social media found.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    @endif

    {{-- Open URL listener for Google Maps --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-url', (event) => {
                window.open(event.url, '_blank');
            });
        });
    </script>
</div>
