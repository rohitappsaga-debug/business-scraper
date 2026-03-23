<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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

        <script id="tailwind-config">
            tailwind.config = {
                darkMode: "class",
                theme: {
                    extend: {
                        colors: {
                            "primary": "#5048e5",
                            "background-light": "#f6f6f8",
                            "background-dark": "#121121",
                        },
                        fontFamily: {
                            "display": ["Inter", "sans-serif"]
                        },
                        borderRadius: {
                            "DEFAULT": "0.25rem",
                            "lg": "0.5rem",
                            "xl": "0.75rem",
                            "full": "9999px"
                        },
                    },
                },
            }
        </script>
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
    </body>
</html>
