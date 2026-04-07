<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Controllers - @yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Tajawal', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Dark Mode Core */
        .dark {
            color-scheme: dark;
        }
        .dark ::-webkit-scrollbar-track { background: #020617; }
        .dark ::-webkit-scrollbar-thumb { background: #1e293b; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #334155; }

        /* Global Dark Overrides */
        .dark .bg-white { background-color: #0f172a !important; }
        .dark .bg-slate-50 { background-color: #020617 !important; }
        .dark .text-slate-900, .dark .text-slate-800, .dark .text-slate-700 { color: #f8fafc !important; }
        .dark .text-slate-600, .dark .text-slate-500 { color: #cbd5e1 !important; }
        
        /* Comprehensive Border Fix */
        .dark .border,
        .dark .border-t,
        .dark .border-b,
        .dark .border-l,
        .dark .border-r,
        .dark .border-slate-300,
        .dark .border-slate-200,
        .dark .border-slate-100,
        .dark .border-slate-50 { 
            border-color: rgba(255, 255, 255, 0.1) !important; 
        }
        
        /* Enhanced Contrast for Cards */
        .dark .rounded-xl.border-slate-200,
        .dark .rounded-2xl.border-slate-200,
        .dark .rounded-3xl.border-slate-200 {
             border-color: rgba(255, 255, 255, 0.15) !important;
        }

        .dark .bg-slate-100 { background-color: #1e293b !important; }
        .dark .hover\:bg-slate-50:hover, 
        .dark .hover\:bg-slate-100:hover,
        .dark .hover\:bg-slate-100\/50:hover { 
            background-color: rgba(255, 255, 255, 0.05) !important; 
        }
        
        .dark header { background-color: #0f172a !important; border-color: rgba(255, 255, 255, 0.08) !important; border-bottom-width: 1px; }
        .dark aside { background-color: #020617 !important; border-color: rgba(255, 255, 255, 0.08) !important; }
        .dark .sticky { background-color: #0f172a !important; z-index: 20; color: #f8fafc !important; }
        .dark thead.sticky, .dark thead .sticky { background-color: #1e293b !important; }
        
        /* Enhanced Buttons & Inputs */
        .dark .bg-indigo-600 { background-color: #4f46e5 !important; }
        .dark .hover\:bg-indigo-700:hover { background-color: #4338ca !important; box-shadow: 0 0 15px rgba(99, 102, 241, 0.4) !important; }
        .dark button.bg-white { background-color: #1e293b !important; border-color: rgba(255, 255, 255, 0.15) !important; color: #f1f5f9 !important; }
        .dark button.bg-white:hover { background-color: #334155 !important; border-color: #6366f1 !important; box-shadow: 0 0 10px rgba(255, 255, 255, 0.05) !important; }
        
        .dark input, .dark select, .dark textarea { background-color: #0f172a !important; border-color: rgba(255, 255, 255, 0.1) !important; color: #f1f5f9 !important; }
        .dark input:focus, .dark select:focus { border-color: #6366f1 !important; ring: 2px #6366f1 !important; }
        
        /* Table & Data Enhancements */
        .dark thead { background-color: #1e293b !important; border-bottom: 2px solid rgba(255, 255, 255, 0.05) !important; }
        .dark tr { border-bottom: 1px solid rgba(255, 255, 255, 0.03) !important; }
        .dark tr:hover { background-color: rgba(255, 255, 255, 0.02) !important; }
        .dark .bg-slate-50\/50 { background-color: rgba(2, 6, 17, 0.4) !important; }
        .dark td.sticky { background-color: #0f172a !important; box-shadow: -4px 0 10px rgba(0,0,0,0.4) !important; }
        .dark th.sticky { background-color: #1e293b !important; box-shadow: -4px 0 10px rgba(0,0,0,0.4) !important; }
        
        /* Specialized Components */
        .dark .bg-indigo-50 { background-color: rgba(99, 102, 241, 0.15) !important; color: #a5b4fc !important; border: 1px solid rgba(99, 102, 241, 0.2) !important; }
        .dark .bg-rose-50 { background-color: rgba(244, 63, 94, 0.15) !important; color: #fda4af !important; border: 1px solid rgba(244, 63, 94, 0.2) !important; }
        .dark .bg-emerald-50 { background-color: rgba(16, 185, 129, 0.1) !important; color: #6ee7b7 !important; border: 1px solid rgba(16, 185, 129, 0.2) !important; }

        /* Shadows & Transitions */
        .dark .shadow-sm, .dark .shadow-md, .dark .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -2px rgba(0, 0, 0, 0.4) !important; }
        
        * {
            transition: background-color 300ms ease, border-color 300ms ease, color 300ms ease, box-shadow 300ms ease;
        }
    </style>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-slate-50 font-sans text-slate-900 transition-colors duration-300" 
    x-data="{ 
        sidebarOpen: true, 
        tableSearch: '', 
        allTables: {{ json_encode($tables ?? $allTables ?? []) }},
        darkMode: localStorage.getItem('db_ctrl_dark') === 'true',
        toggleMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('db_ctrl_dark', this.darkMode);
        }
    }" :class="darkMode ? 'dark bg-slate-950 text-slate-100' : ''">
    <div class="h-screen flex overflow-hidden">
        
        <!-- Sidebar -->
        <aside 
            class="bg-slate-900 text-slate-300 w-72 flex-shrink-0 transition-all duration-300 ease-in-out border-r border-slate-800 flex flex-col z-40 fixed h-full md:relative md:h-screen md:sticky md:top-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0 md:w-20'"
        >
            <!-- Sidebar Header -->
            <div class="h-16 flex items-center gap-3 px-6 bg-slate-950/50 border-b border-slate-800/50 flex-shrink-0">
                <i class="fa-solid fa-database text-indigo-400 text-xl"></i>
                <span class="font-bold text-lg text-white whitespace-nowrap overflow-hidden transition-all duration-300" x-show="sidebarOpen">
                    Database <span class="text-indigo-400">Controllers</span>
                </span>
            </div>

            <!-- Sidebar Navigation -->
            <div class="flex-grow flex flex-col pt-6 px-4 overflow-hidden min-h-0">
                
                <!-- Main Menu -->
                <div class="space-y-3 flex-shrink-0 mb-7">
                    <h4 class="text-[10px] uppercase font-bold text-slate-500 tracking-wider mb-2 px-2" x-show="sidebarOpen">{{ __('database-controllers::messages.general') }}</h4>
                    <a href="{{ route('database-controllers.index') }}" class="flex items-center px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white transition group @if(Route::is('database-controllers.index')) bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 @endif">
                        <i class="fa-solid fa-gauge w-6 text-center text-lg @if(!Route::is('database-controllers.index')) text-slate-500 group-hover:text-indigo-400 @endif"></i>
                        <span class="ms-3 font-medium text-sm transition-all duration-300" x-show="sidebarOpen">{{ __('database-controllers::messages.dashboard') }}</span>
                    </a>
                    <a href="{{ route('database-controllers.backup') }}" class="flex items-center px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white transition group @if(Route::is('database-controllers.backup')) bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 @endif">
                        <i class="fa-solid fa-cloud-arrow-down w-6 text-center text-lg @if(!Route::is('database-controllers.backup')) text-slate-500 group-hover:text-amber-400 @endif"></i>
                        <span class="ms-3 font-medium text-sm transition-all duration-300" x-show="sidebarOpen">{{ __('database-controllers::messages.backups') }}</span>
                    </a>
                </div>

                <!-- Tables Section -->
                <div class="flex-grow flex flex-col min-h-0 pt-4" x-show="sidebarOpen">
                    <h3 class="px-3 text-[10px] font-black uppercase tracking-[.2em] text-slate-500 mb-4 flex items-center">
                        <i class="fa-solid fa-list-ul me-2 text-indigo-500"></i>
                        {{ __('database-controllers::messages.tables') }}
                    </h3>
                    
                    <!-- Search Tables -->
                    <div class="mb-4 px-1 relative group">
                        <i class="fa-solid fa-magnifying-glass absolute start-4 top-1/2 -translate-y-1/2 text-slate-500 text-[10px] pointer-events-none group-focus-within:text-indigo-400 transition-colors"></i>
                        <input 
                            type="text" 
                            x-model="tableSearch"
                            placeholder="{{ __('database-controllers::messages.filter_tables') }}" 
                            class="w-full bg-slate-800/50 border border-slate-700/50 rounded-lg py-2 ps-9 pe-4 text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500/50 focus:border-indigo-500/50 focus:bg-slate-800 transition-all font-medium placeholder:text-slate-600"
                        >
                    </div>

                    <!-- Filtered Tables List -->
                    <div class="flex-grow h-[calc(100vh-288px)] overflow-y-auto px-1 ltr:pr-2 rtl:pl-2 scrollbar-thin">
                        <div class="space-y-1">
                            <template x-for="table in allTables.filter(t => t.name.toLowerCase().includes(tableSearch.toLowerCase()))" :key="table.name">
                                <a 
                                    :href="'{{ route('database-controllers.table.show', 'TABLE') }}'.replace('TABLE', table.name)" 
                                    class="flex items-center px-3 py-2 rounded-md hover:bg-slate-800 hover:text-white transition text-xs group"
                                    :class="'{{ $table ?? '' }}' == table.name ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/20' : 'text-slate-400'"
                                >
                                    <i class="fa-solid fa-table w-4 text-center ltr:mr-2 rtl:ml-2 opacity-50 group-hover:opacity-100 group-hover:text-indigo-400"></i>
                                    <span x-text="table.name" class="truncate flex-grow"></span>
                                    <span x-text="table.formatted_count" class="text-[9px] font-bold opacity-40 group-hover:opacity-100 ltr:ml-2 rtl:mr-2 bg-slate-800 px-1.5 py-0.5 rounded text-slate-300"></span>
                                    <i class="fa-solid fa-chevron-right ms-2 rtl:rotate-180 text-[8px] opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-1 rtl:translate-x-0 group-hover:translate-x-0 rtl:group-hover:-translate-x-1"></i>
                                </a>
                            </template>
                        </div>
                        <div x-show="allTables.filter(t => t.name.toLowerCase().includes(tableSearch.toLowerCase())).length === 0" class="py-10 text-center text-[10px] text-slate-600 italic">
                            {{ __('database-controllers::messages.no_tables_found') }}
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-grow flex flex-col min-w-0 overflow-y-auto overflow-x-hidden">
            <!-- Header -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-30 flex-shrink-0">
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-slate-500 hover:text-slate-800 p-2 rounded-md hover:bg-slate-100 transition">
                        <i class="fa-solid fa-bars-staggered text-xl transition-all duration-300" :class="sidebarOpen ? 'rotate-180' : ''"></i>
                    </button>
                    <div class="ms-4 h-6 w-px bg-slate-200"></div>
                    <div class="ms-4 flex items-center gap-2">
                        <h2 class="text-sm font-bold text-slate-800">@yield('title')</h2>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="relative" x-data="{ langOpen: false }" @click.away="langOpen = false" @keydown.escape.window="langOpen = false">
                        @php
                            $currentFlag = match(app()->getLocale()) {
                                'en' => ['name' => 'English', 'image' => 'united-states.png'],
                                'ar' => ['name' => 'العربية', 'image' => 'egypt.png'],
                                'fr' => ['name' => 'Français', 'image' => 'france.png'],
                                'es' => ['name' => 'Español', 'image' => 'spain.png']
                            };
                        @endphp
                        <button @click="langOpen = !langOpen" class="flex items-center h-10 px-3 rounded-xl border border-slate-200 transition-all duration-300 hover:border-indigo-500 hover:bg-slate-50 shadow-sm relative group overflow-hidden" :class="darkMode ? 'bg-slate-800 border-slate-700 hover:bg-slate-700' : 'bg-white'">
                            <img src="{{ route('database-controllers.image.serve', $currentFlag['image']) }}" alt="flag" class="w-5 h-3.5 rounded shadow-sm me-2 transition-all object-cover">
                            <span class="text-[10px] font-black tracking-widest text-indigo-600 uppercase">{{ $currentFlag['name'] }}</span>
                            <i class="fa-solid fa-chevron-down ms-2 text-[9px] text-slate-400 transition-transform duration-300" :class="langOpen ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="langOpen" x-cloak 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                             class="absolute end-0 mt-0.5 w-fit bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] py-2 z-50 overflow-hidden ring-1 ring-slate-950/5"
                        >
                            @php
                                $langs = [
                                    'en' => ['name' => 'English', 'image' => 'united-states.png'],
                                    'ar' => ['name' => 'العربية', 'image' => 'egypt.png'],
                                    'fr' => ['name' => 'Français', 'image' => 'france.png'],
                                    'es' => ['name' => 'Español', 'image' => 'spain.png']
                                ];
                            @endphp
                            @foreach($langs as $code => $info)
                                <a href="{{ route('database-controllers.switch-locale', $code) }}" 
                                   class="{{ app()->getLocale() == $code ? 'bg-indigo-50/50 dark:bg-slate-800/80' : '' }} flex items-center justify-center px-5 py-2.5 text-xs font-bold transition-all duration-200 hover:bg-indigo-50/50 dark:hover:bg-slate-800/80 group"
                                >
                                    <div class="flex items-center">
                                        <div class="me-3 flex flex-col items-center">
                                            <img src="{{ route('database-controllers.image.serve', $info['image']) }}" class="w-6 h-4 rounded shadow-sm transition-all object-cover">
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="{{ app()->getLocale() == $code ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-300' }} text-[9px] uppercase">{{ $info['name'] }}</span>
                                        </div>
                                    </div>
                                </a>
                                @if(!$loop->last)
                                    <div class="h-px bg-slate-100 dark:bg-slate-800/50 mx-4 my-1"></div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <button @click="toggleMode()" class="p-2 w-10 h-10 rounded-xl border border-slate-200 transition-all duration-300 hover:border-indigo-500 hover:bg-slate-50 relative group overflow-hidden" :class="darkMode ? 'bg-slate-800 border-slate-700 hover:bg-slate-700' : 'bg-white'">
                        <div class="relative w-full h-full">
                            <i class="fa-solid fa-sun absolute inset-0 flex items-center justify-center text-amber-500 transition-all duration-500 transform" :class="darkMode ? '-translate-y-full opacity-0' : 'translate-y-0 opacity-100'"></i>
                            <i class="fa-solid fa-moon absolute inset-0 flex items-center justify-center text-indigo-400 transition-all duration-500 transform" :class="darkMode ? 'translate-y-0 opacity-100' : 'translate-y-full opacity-0'"></i>
                        </div>
                    </button>
                    @if(!empty(config('database-controllers.password')))
                        <form action="{{ route('database-controllers.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 px-3 py-1.5 rounded-lg border border-transparent hover:border-rose-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 transition duration-300 group">
                                <span class="text-[11px] font-black uppercase tracking-widest">{{ __('database-controllers::messages.logout') }}</span>
                                <i class="fa-solid fa-arrow-right-from-bracket rtl:rotate-180 text-base"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6 md:p-8 flex-grow">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-emerald-50 border-emerald-500/50 border rounded-lg flex items-center text-emerald-800 shadow-sm animate-fadeIn">
                        <div class="w-8 h-8 bg-emerald-500 text-white rounded-full flex items-center justify-center ltr:mr-3 rtl:ml-3 shadow-sm">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <span class="font-medium font-sm">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-rose-50 border-rose-500/50 border rounded-lg flex items-center text-rose-800 shadow-sm animate-fadeIn">
                        <div class="w-8 h-8 bg-rose-500 text-white rounded-full flex items-center justify-center ltr:mr-3 rtl:ml-3 shadow-sm">
                            <i class="fa-solid fa-times"></i>
                        </div>
                        <span class="font-medium font-sm">{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Page Footer -->
            <footer class="py-4 px-8 border-t border-slate-200 bg-white text-center md:text-left flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-xs text-slate-500 font-medium">
                    {{ __('database-controllers::messages.made_by') }} ❤️ <a href="https://github.com/httpsnader1" target="_blank" class="text-slate-400">Mohamed Nader</a>
                </p>
                <div class="flex items-center space-x-4 text-xs font-bold text-slate-400">
                    <a href="#" class="hover:text-indigo-600 transition tracking-wider uppercase">Documentation</a>
                    <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                    <a href="#" class="hover:text-indigo-600 transition tracking-wider uppercase">Support</a>
                </div>
            </footer>
        </div>
    </div>

    <style>
        .scrollbar-thin::-webkit-scrollbar { width: 4px; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn { animation: fadeIn 0.4s ease-out forwards; }
    </style>
</body>
</html>
