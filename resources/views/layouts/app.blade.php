<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&display=swap" rel="stylesheet"/>
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
        
        <script>
            if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        @livewireStyles
    </head>
    <body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100">
        {{ $slot }}

        <!-- Theme Toggle -->
        <div x-data="{ 
            darkMode: localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
            toggle() {
                this.darkMode = !this.darkMode;
                if (this.darkMode) {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                }
            }
        }" class="fixed bottom-6 right-6 z-[100]">
            <button @click="toggle" 
                    class="group relative flex h-14 w-14 items-center justify-center rounded-full bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700 hover:scale-110 active:scale-95 transition-all duration-300" 
                    :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
                
                <span class="material-symbols-outlined text-amber-500 transition-all duration-500"
                      :class="darkMode ? 'rotate-90 scale-0 opacity-0' : 'rotate-0 scale-100 opacity-100 absolute'">
                    light_mode
                </span>
                
                <span class="material-symbols-outlined text-blue-400 transition-all duration-500"
                      :class="darkMode ? 'rotate-0 scale-100 opacity-100 absolute' : '-rotate-90 scale-0 opacity-0'">
                    dark_mode
                </span>

                <div class="absolute inset-0 rounded-full border-2 border-primary opacity-0 group-hover:opacity-100 group-hover:animate-ping pointer-events-none"></div>
            </button>
        </div>
        <!-- Global Confirmation Modal -->
        <div x-data="{ 
            open: false, 
            title: '', 
            message: '', 
            confirmButton: 'Confirm', 
            cancelButton: 'Cancel', 
            type: 'primary', 
            confirmAction: null,
            confirmActionUrl: null,
            onConfirm: null,
            
            show(detail) {
                // Handle Livewire 3 event structure where data might be in detail[0]
                const data = Array.isArray(detail) ? detail[0] : detail;
                
                this.title = data.title || 'Are you sure?';
                this.message = data.message || '';
                this.confirmButton = data.confirmButton || 'Confirm';
                this.cancelButton = data.cancelButton || 'Cancel';
                this.type = data.type || 'primary';
                this.confirmAction = data.confirmAction || null;
                this.confirmActionUrl = data.confirmActionUrl || null;
                this.onConfirm = data.onConfirm;
                this.open = true;
            },
            confirm() {
                if (this.confirmActionUrl) {
                    window.location.href = this.confirmActionUrl;
                    return;
                }
                
                if (this.confirmAction) {
                    if (typeof this.confirmAction === 'object' && this.confirmAction !== null) {
                        Livewire.dispatch(this.confirmAction.name, this.confirmAction.data);
                    } else {
                        Livewire.dispatch(this.confirmAction);
                    }
                } else if (this.onConfirm && typeof this.onConfirm === 'function') {
                    this.onConfirm();
                }
                this.open = false;
            }
        }" 
        x-on:open-confirm-modal.window="show($event.detail)">
            <template x-if="open">
                <div class="fixed inset-0 z-[200] overflow-y-auto">
                    <!-- Backdrop -->
                    <div x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" 
                         @click="open = false"></div>

                    <!-- Modal Positioner -->
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <!-- Modal Panel -->
                        <div x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                             class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200 dark:border-slate-800">
                            
                            <div class="bg-white dark:bg-slate-900 px-6 pt-6 pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10"
                                         :class="{
                                             'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400': type === 'primary',
                                             'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400': type === 'danger',
                                             'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400': type === 'warning'
                                         }">
                                        <span class="material-symbols-outlined" x-text="type === 'danger' ? 'report' : (type === 'warning' ? 'warning' : 'help')"></span>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                        <h3 class="text-xl font-bold leading-6 text-slate-900 dark:text-white" x-text="title"></h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium leading-relaxed" x-text="message"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-slate-50 dark:bg-slate-800/50 px-6 py-4 flex flex-row-reverse gap-3">
                                <button type="button" 
                                        class="inline-flex w-full justify-center rounded-xl px-5 py-2.5 text-sm font-bold shadow-sm sm:w-auto transition-all active:scale-95 text-white"
                                        :class="{
                                            'bg-primary hover:bg-primary/90': type === 'primary',
                                            'bg-red-600 hover:bg-red-500': type === 'danger',
                                            'bg-amber-600 hover:bg-amber-500': type === 'warning'
                                        }"
                                        @click="confirm()">
                                    <span x-text="confirmButton"></span>
                                </button>
                                <button type="button" 
                                        class="inline-flex w-full justify-center rounded-xl px-5 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 sm:w-auto transition-all active:scale-95"
                                        @click="open = false">
                                    <span x-text="cancelButton"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <style>
            [x-cloak] { display: none !important; }
        </style>
        @livewireScripts
    </body>
</html>
