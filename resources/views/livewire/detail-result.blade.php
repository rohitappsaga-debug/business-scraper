<div>
    @if (session()->has('message'))
        <div class="max-w-full mx-auto w-full px-4 md:px-8 pt-4">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('message') }}
            </div>
        </div>
    @endif

    @if (empty($business))
        <main class="max-w-full mx-auto w-full px-4 md:px-8 pt-12 pb-8 text-center">
            <p class="text-slate-500 dark:text-slate-400">Business lead not found.</p>
            <button wire:click="backToResults" class="mt-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                Back to Results
            </button>
        </main>
    @else
        <main class="max-w-full mx-auto w-full px-4 md:px-8 pt-12 pb-8">
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
                    <button wire:click="generateMasterPrompt" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg text-sm font-bold transition-all flex items-center gap-2 shadow-sm">
                        <span class="material-symbols-outlined">smart_toy</span>
                        Generate Master Prompt
                    </button>
                    <a href="{{ route('logout') }}" @click.prevent="$wire.confirmLogout()" class="p-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shadow-sm flex items-center justify-center group cursor-pointer" title="Logout">
                        <span class="material-symbols-outlined group-hover:scale-110 transition-transform pointer-events-none">logout</span>
                    </a>
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
                            <div class="mt-8 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 aspect-[21/9] relative bg-slate-100 dark:bg-slate-800">
                                @if(!empty(config('services.google.maps_api_key')))
                                    {{-- Using Google Maps Embed API with key --}}
                                    <iframe
                                        width="100%"
                                        height="100%"
                                        style="border:0"
                                        loading="lazy"
                                        allowfullscreen
                                        referrerpolicy="no-referrer-when-downgrade"
                                        src="https://www.google.com/maps/embed/v1/place?key={{ config('services.google.maps_api_key') }}&q={{ urlencode($business['address']) }}&zoom=15">
                                    </iframe>
                                @else
                                    {{-- Using the search embed which works without a key --}}
                                    <iframe
                                        class="w-full h-full"
                                        frameborder="0" style="border:0"
                                        src="https://www.google.com/maps?q={{ urlencode($business['address']) }}&output=embed"
                                        allowfullscreen>
                                    </iframe>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- AI Master Prompt Section --}}
                    @if($generatedPrompt)
                        <div
                            x-data="{
                                copied: false,
                                copyPrompt() {
                                    navigator.clipboard.writeText(this.$refs.promptText.value);
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 2000);
                                }
                            }"
                            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-primary/20 dark:border-primary/30 overflow-hidden"
                        >
                            <div class="px-6 py-4 border-b border-primary/10 dark:border-primary/10 flex justify-between items-center bg-primary/5">
                                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">auto_awesome</span>
                                    AI Master Prompt
                                </h3>
                                <button
                                    @click="copyPrompt"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                    :class="copied ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' : 'bg-primary text-white hover:bg-primary/90'"
                                >
                                    <span class="material-symbols-outlined text-[18px]" x-text="copied ? 'check' : 'content_copy'"></span>
                                    <span x-text="copied ? 'Copied!' : 'Copy Prompt'"></span>
                                </button>
                            </div>
                            <div class="p-6">
                                <textarea
                                    x-ref="promptText"
                                    readonly
                                    class="w-full h-64 p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-mono text-sm leading-relaxed focus:ring-primary focus:border-primary resize-none"
                                >{{ $generatedPrompt }}</textarea>
                                <p class="mt-3 text-xs text-slate-400 italic">
                                    This prompt is optimized for ChatGPT, Claude, and other AI agents. Copy and paste it to generate personalized growth strategies.
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- AI Collaboration Email Draft Moved Here --}}
                    @if(isset($business['id']))
                        <livewire:business-email-draft :business="\App\Models\Business::find($business['id'])" />
                    @endif
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
                                    @if(!empty($business['website']))
                                        <a
                                            href="{{ str_starts_with($business['website'], 'http') ? $business['website'] : 'https://' . $business['website'] }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-bold hover:opacity-90 transition-all shadow-sm"
                                            title="Visit Website"
                                        >
                                            <span>Visit Site</span>
                                            <span class="material-symbols-outlined text-[16px]">open_in_new</span>
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

                    {{-- Additional Details (Moved to Sidebar) --}}
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">info</span>
                                Additional Details
                            </h3>
                        </div>
                        <div class="p-6 space-y-6">
                            {{-- Performance --}}
                            <div class="space-y-3">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Performance</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $business['rating'] }}</span>
                                    <div class="flex text-yellow-400">
                                        <span class="material-symbols-outlined fill-current text-[16px]">star</span>
                                        <span class="material-symbols-outlined fill-current text-[16px]">star</span>
                                        <span class="material-symbols-outlined fill-current text-[16px]">star</span>
                                        <span class="material-symbols-outlined fill-current text-[16px]">star</span>
                                        <span class="material-symbols-outlined text-[16px]">star_half</span>
                                    </div>
                                </div>
                                <p class="text-slate-500 dark:text-slate-400 text-xs">Based on {{ $business['review_count'] }} reviews</p>
                                @if(!empty($business['website_url']))
                                    <a href="{{ $business['website_url'] }}" target="_blank" class="inline-flex items-center gap-1.5 text-primary text-xs font-bold hover:underline">
                                        <span class="material-symbols-outlined text-[16px]">store</span>
                                        Google Business Profile
                                    </a>
                                @endif
                            </div>

                            {{-- Follow Us (Social Media) --}}
                            <div class="space-y-3 pt-4 border-t border-slate-50 dark:border-slate-800">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Follow Us</p>
                                @if(!empty($business['social']))
                                    <div class="flex flex-wrap gap-3">
                                        @foreach($business['social'] as $social)
                                            @php
                                                $platform = strtolower($social['platform']);
                                                $url = $social['url'];
                                            @endphp
                                            @if(!empty($url))
                                                <a href="{{ $url }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="group relative size-10 rounded-xl bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition-all duration-300 shadow-sm hover:shadow-primary/20 hover:-translate-y-0.5"
                                                   title="{{ ucfirst($platform) }}">
                                                    @if($platform === 'facebook')
                                                        <svg class="size-5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                                    @elseif($platform === 'instagram')
                                                        <svg class="size-5 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                                    @elseif($platform === 'twitter' || $platform === 'x')
                                                        <svg class="size-5 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                                    @elseif($platform === 'linkedin')
                                                        <svg class="size-5 fill-current" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                                    @elseif($platform === 'youtube')
                                                        <svg class="size-5 fill-current" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                                                    @else
                                                        <span class="material-symbols-outlined text-[20px]">link</span>
                                                    @endif

                                                    {{-- Tooltip style hint --}}
                                                    <span class="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none font-bold">
                                                        {{ ucfirst($platform) }}
                                                    </span>
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 text-slate-400 text-xs italic">
                                        <span class="material-symbols-outlined text-[14px]">link_off</span>
                                        <span>No social links found</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Business Hours --}}
                            <div class="space-y-3 pt-4 border-t border-slate-50 dark:border-slate-800">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Business Hours</p>
                                <ul class="text-[13px] space-y-2">
                                    @foreach($business['hours'] as $hour)
                                        <li class="flex justify-between {{ $hour['time'] === 'Closed' ? 'text-slate-400' : 'text-slate-600 dark:text-slate-400' }}">
                                            <span class="font-medium">{{ $hour['day'] }}</span>
                                            <span class="{{ $hour['time'] === 'Closed' ? '' : 'text-slate-900 dark:text-slate-200 font-semibold' }}">{{ $hour['time'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Additional Details Script (Removed but kept marker for potential next steps) --}}

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
