<div class="relative min-h-screen w-full flex flex-col items-center justify-center bg-[#0B0A15] font-sans overflow-hidden px-4">
    <!-- Subtle Background Glows -->
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-500/10 blur-[120px] rounded-full"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-blue-500/10 blur-[120px] rounded-full"></div>
    </div>

    <!-- Main Container -->
    <div class="relative w-full max-w-[440px] z-10">
        <!-- Brand Header -->
        <div class="flex flex-col items-center mb-12 text-center decoration-none">
            <div class="group relative mb-8">
                <div class="absolute -inset-4 bg-indigo-500/20 blur-2xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                <div class="relative w-16 h-16 rounded-[22px] bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center shadow-2xl shadow-indigo-500/40 transition-all duration-500 group-hover:scale-110 group-hover:rotate-3">
                    <span class="material-symbols-outlined text-white text-3xl font-light">query_stats</span>
                </div>
            </div>
            <h1 class="text-4xl font-extrabold text-white tracking-tight mb-3">Welcome back</h1>
            <p class="text-gray-400 text-base font-medium max-w-[280px] leading-relaxed">Enter your credentials to access business terminal</p>
        </div>

        <!-- Login Card -->
        <div class="group/card relative">
            <!-- Animated border glow -->
            <div class="absolute -inset-[1px] bg-gradient-to-b from-white/20 via-white/5 to-transparent rounded-[2.5rem] pointer-events-none"></div>
            
            <div class="relative bg-[#161525]/80 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] shadow-[0_40px_80px_-20px_rgba(0,0,0,0.8)] overflow-hidden">
                <!-- Inner glass highlight -->
                <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>
                
                <div class="p-10 md:p-12">
                    @if ($errors->has('username'))
                        <div class="mb-8 p-4 text-sm text-red-400 bg-red-400/5 border border-red-400/20 rounded-2xl flex items-center gap-3 animate-shake">
                            <span class="material-symbols-outlined text-xl">error</span>
                            <span class="font-medium">{{ $errors->first('username') }}</span>
                        </div>
                    @endif

                    <form wire:submit="authenticate" class="space-y-6">
                        <!-- Email Field -->
                        <div class="space-y-2.5">
                            <label for="username" class="text-xs font-bold uppercase tracking-widest text-gray-500 ml-1">Email Address</label>
                            <div class="relative group/input">
                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-gray-400 group-focus-within/input:text-indigo-400 transition-colors z-10">
                                    <span class="material-symbols-outlined text-xl font-light">alternate_email</span>
                                </div>
                                <input wire:model="username" id="username" type="email" 
                                    class="w-full h-14 pl-14 pr-5 bg-black/20 border border-white/5 rounded-2xl text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/50 focus:bg-black/40 transition-all duration-300 text-base" 
                                    placeholder="name@company.com" required autofocus />
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between ml-1">
                                <label for="password" class="text-xs font-bold uppercase tracking-widest text-gray-500">Password</label>
                            </div>
                            <div class="relative group/input">
                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-gray-400 group-focus-within/input:text-indigo-400 transition-colors z-10">
                                    <span class="material-symbols-outlined text-xl font-light">vpn_key</span>
                                </div>
                                <input wire:model="password" id="password" type="{{ $showPassword ? 'text' : 'password' }}" 
                                    class="w-full h-14 pl-14 pr-14 bg-black/20 border border-white/5 rounded-2xl text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/50 focus:bg-black/40 transition-all duration-300 text-base" 
                                    placeholder="••••••••" required />
                                
                                <button type="button" wire:click="togglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center text-gray-500 hover:text-white hover:bg-white/5 rounded-xl transition-all duration-200 z-10">
                                    <span class="material-symbols-outlined text-xl font-light">{{ $showPassword ? 'visibility_off' : 'visibility' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button type="submit" wire:loading.attr="disabled" class="relative overflow-hidden w-full h-14 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold rounded-2xl shadow-2xl shadow-indigo-600/20 transition-all duration-300 flex items-center justify-center group/btn">
                                <span wire:loading.remove wire:target="authenticate" class="flex items-center gap-2 relative z-10">
                                    <span>Sign in to account</span>
                                    <span class="material-symbols-outlined text-xl group-hover/btn:translate-x-1 transition-transform">arrow_forward</span>
                                </span>
                                <span wire:loading.flex wire:target="authenticate" class="hidden items-center justify-center gap-3 relative z-10">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Authorizing...</span>
                                </span>
                                <!-- Button hover shine -->
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover/btn:animate-[shimmer_1.5s_infinite]"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <style>
        .animate-shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
    </style>
</div>
