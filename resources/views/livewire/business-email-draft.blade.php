<div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
        <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">smart_toy</span>
            AI Collaboration Email Draft
        </h3>
        @if($draftId)
            <div class="flex gap-2">
                <button wire:click="toggleEdit" class="text-slate-400 hover:text-primary transition-colors" title="Edit Draft">
                    <span class="material-symbols-outlined text-[20px]">{{ $isEditing ? 'visibility' : 'edit' }}</span>
                </button>
                <button wire:click="regenerate" wire:loading.attr="disabled" class="text-slate-400 hover:text-primary transition-colors disabled:opacity-50" title="Regenerate Draft">
                    <span class="material-symbols-outlined text-[20px]" wire:loading.class="animate-spin" wire:target="regenerate">refresh</span>
                </button>
                <button wire:click="copyToClipboard" class="text-slate-400 hover:text-primary transition-colors" title="Copy Body">
                    <span class="material-symbols-outlined text-[20px]">content_copy</span>
                </button>
            </div>
        @endif
    </div>

    <div class="p-6">
        @if(session()->has('success'))
            <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-2 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session()->has('error'))
            <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-2 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if($isGenerating)
            <div class="flex flex-col items-center justify-center py-12 space-y-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Generating professional draft using AI...</p>
            </div>
        @elseif($draftId)
            <div class="space-y-4">
                <div class="space-y-1">
                    <div class="flex justify-between items-center">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Subject Line</label>
                        @if(!$isEditing)
                            <button wire:click="copySubject" class="text-slate-400 hover:text-primary transition-colors flex items-center gap-1 text-[10px] font-bold" title="Copy Subject">
                                <span class="material-symbols-outlined text-[14px]">content_copy</span>
                                COPY
                            </button>
                        @endif
                    </div>
                    @if($isEditing)
                        <input type="text" wire:model="subject" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    @else
                        <p class="text-slate-900 dark:text-white font-semibold leading-relaxed">{{ $subject }}</p>
                    @endif
                </div>

                <div class="space-y-1">
                    <div class="flex justify-between items-center">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Email Body</label>
                        @if(!$isEditing)
                            <button wire:click="copyBody" class="text-slate-400 hover:text-primary transition-colors flex items-center gap-1 text-[10px] font-bold" title="Copy Body">
                                <span class="material-symbols-outlined text-[14px]">content_copy</span>
                                COPY
                            </button>
                        @endif
                    </div>
                    @if($isEditing)
                        <textarea wire:model="emailBody" rows="10" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all resize-none"></textarea>
                        <div class="flex justify-end mt-2">
                            <button wire:click="saveDraft" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-primary/90 transition-all">
                                Save Changes
                            </button>
                        </div>
                    @else
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-5 border border-slate-100 dark:border-slate-800/50">
                            <div class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-line space-y-4">
                                {!! nl2br(e($emailBody)) !!}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-8 text-center space-y-4">
                <div class="size-16 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-300">
                    <span class="material-symbols-outlined text-4xl">mail</span>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 dark:text-white">No draft generated yet</h4>
                    <p class="text-slate-500 dark:text-slate-400 text-sm max-w-xs mx-auto mt-1">Generate a professional AI-powered outreach email for this business lead.</p>
                </div>
                <button wire:click="generateDraft" wire:loading.attr="disabled" class="bg-primary text-white px-6 py-2.5 rounded-lg text-sm font-bold hover:bg-primary/90 transition-all flex items-center gap-2 group disabled:opacity-50">
                    <span class="material-symbols-outlined text-[20px] group-hover:rotate-12 transition-transform">bolt</span>
                    Generate AI Draft
                </button>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.on('copy-to-clipboard', (event) => {
                const text = event.text;
                navigator.clipboard.writeText(text).then(() => {
                    // Success already handled by flash message in component
                }).catch(err => {
                    console.error('Could not copy text: ', err);
                });
            });
        });
    </script>
</div>
