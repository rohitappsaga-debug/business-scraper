<div>
    <div class="relative flex h-screen w-full overflow-hidden bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100">
        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col overflow-y-auto w-full items-center justify-center min-h-screen">
            <!-- Page Content -->
            <div class="max-w-4xl mx-auto w-full px-8 py-8">
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('search') }}" wire:navigate class="p-2 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary transition-colors flex items-center justify-center shadow-sm">
                            <span class="material-symbols-outlined">arrow_back</span>
                        </a>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Settings</h1>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 pl-[52px]">Configure your account and application preferences.</p>
                </div>

                @if (session()->has('success'))
                    <div class="mb-4 p-4 text-green-700 bg-green-100 rounded-lg dark:bg-green-800/20 dark:text-green-400 border border-green-200 dark:border-green-800/50 flex items-center gap-3" role="alert">
                        <span class="material-symbols-outlined">check_circle</span>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                @endif

                <!-- Form Card -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="p-1 bg-gradient-to-r from-primary/50 via-primary/20 to-transparent"></div>
                    <div class="p-8">
                        <form wire:submit="save" class="space-y-8">
                            <div class="space-y-6">
                                <!-- Email Sender Name -->
                                <div class="flex flex-col gap-3">
                                    <div class="flex items-center justify-between">
                                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Email Sender Name</label>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 uppercase tracking-wider">Required</span>
                                    </div>
                                    <div class="group flex items-center bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary transition-all overflow-hidden">
                                        <div class="flex items-center justify-center pl-4 pr-3 text-slate-400 group-focus-within:text-primary transition-colors">
                                            <span class="material-symbols-outlined">person_edit</span>
                                        </div>
                                        <input wire:model="emailSenderName" class="block w-full py-4 pr-4 bg-transparent border-0 focus:ring-0 outline-none placeholder:text-slate-400 text-slate-900 dark:text-white" placeholder="e.g. John Marketing Team, GrowthX Agency" type="text" />
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">This name will be used by the AI when generating collaboration email drafts instead of the default 'Laravel Team'.</p>
                                    @error('emailSenderName') <span class="text-red-500 text-xs font-medium">{{ $message }}</span> @enderror
                                </div>

                                <!-- Preview Section -->
                                <div class="p-6 bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-dashed border-slate-300 dark:border-slate-700">
                                    <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                        Email Signature Preview
                                    </h3>
                                    <div class="text-slate-600 dark:text-slate-300 italic font-serif">
                                        Best regards,<br>
                                        <span class="font-bold text-primary not-italic font-sans">{{ $emailSenderName ?: 'Your Team' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-4 pt-4">
                                <button class="bg-primary hover:bg-primary-dark text-white font-bold py-4 px-8 rounded-xl shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-3 w-full group" type="submit">
                                    <span wire:loading.remove wire:target="save" class="material-symbols-outlined group-hover:scale-110 transition-transform">save</span>
                                    <span wire:loading wire:target="save" class="animate-spin material-symbols-outlined">progress_activity</span>
                                    <span wire:loading.remove wire:target="save">Save Changes</span>
                                    <span wire:loading wire:target="save">Saving...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Security Note -->
                <div class="mt-8 flex items-center justify-center gap-2 text-slate-400 dark:text-slate-600 text-[10px] font-medium uppercase tracking-widest">
                    <span class="material-symbols-outlined text-xs">lock</span>
                    <span>All settings are securely persisted in your account</span>
                </div>
            </div>
        </main>
    </div>
</div>
