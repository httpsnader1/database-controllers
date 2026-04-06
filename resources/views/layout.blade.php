<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Controllers - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-slate-50 font-sans text-slate-900" x-data="{ 
    sidebarOpen: true, 
    tableSearch: '', 
    allTables: {{ json_encode($tables ?? $allTables ?? []) }} 
}">
    <div class="h-screen flex overflow-hidden">
        
        <!-- Sidebar -->
        <aside 
            class="bg-slate-900 text-slate-300 w-72 flex-shrink-0 transition-all duration-300 ease-in-out border-r border-slate-800 flex flex-col z-40 fixed h-full md:relative md:h-screen md:sticky md:top-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0 md:w-20'"
        >
            <!-- Sidebar Header -->
            <div class="h-16 flex items-center px-6 bg-slate-950/50 border-b border-slate-800/50 flex-shrink-0">
                <i class="fa-solid fa-database text-indigo-400 text-xl"></i>
                <span class="ml-3 font-bold text-lg text-white whitespace-nowrap overflow-hidden transition-all duration-300" x-show="sidebarOpen">
                    DB<span class="text-indigo-400">Controllers</span>
                </span>
            </div>

            <!-- Sidebar Navigation -->
            <div class="flex-grow flex flex-col pt-6 px-4 overflow-hidden min-h-0">
                
                <!-- Main Menu -->
                <div class="space-y-3 flex-shrink-0 mb-7">
                    <h4 class="text-[10px] uppercase font-bold text-slate-500 tracking-wider mb-2 px-2" x-show="sidebarOpen">General</h4>
                    <a href="{{ route('database-controllers.index') }}" class="flex items-center px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white transition group @if(Route::is('database-controllers.index')) bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 @endif">
                        <i class="fa-solid fa-gauge w-6 text-center text-lg @if(!Route::is('database-controllers.index')) text-slate-500 group-hover:text-indigo-400 @endif"></i>
                        <span class="ml-3 font-medium text-sm transition-all duration-300" x-show="sidebarOpen">Dashboard</span>
                    </a>
                    <a href="{{ route('database-controllers.backup') }}" class="flex items-center px-3 py-2.5 rounded-lg hover:bg-slate-800 hover:text-white transition group @if(Route::is('database-controllers.backup')) bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 @endif">
                        <i class="fa-solid fa-cloud-arrow-down w-6 text-center text-lg @if(!Route::is('database-controllers.backup')) text-slate-500 group-hover:text-amber-400 @endif"></i>
                        <span class="ml-3 font-medium text-sm transition-all duration-300" x-show="sidebarOpen">Backups</span>
                    </a>
                </div>

                <!-- Tables List with Search -->
                <div class="flex-grow flex flex-col min-h-0 space-y-3" x-show="sidebarOpen">
                    <div class="px-2 flex items-center justify-between">
                        <h4 class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Tables</h4>
                        <span class="text-[10px] bg-slate-800 text-slate-400 px-1.5 py-0.5 rounded" x-text="allTables.length"></span>
                    </div>
                    
                    <!-- Table Filter Input -->
                    <div class="relative px-2">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                        <input 
                            type="text" 
                            x-model="tableSearch" 
                            placeholder="Filter tables..." 
                            class="w-full bg-slate-800/50 border border-slate-700/50 rounded-md py-1.5 pl-8 pr-3 text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-slate-800 outline-none transition text-slate-200"
                        >
                    </div>

                    <!-- Filtered Tables List -->
                    <div class="flex-grow overflow-y-auto px-1 pr-2 scrollbar-thin">
                        <div class="space-y-1">
                            <template x-for="table in allTables.filter(t => t.name.toLowerCase().includes(tableSearch.toLowerCase()))" :key="table.name">
                                <a 
                                    :href="'{{ route('database-controllers.table.show', 'TABLE') }}'.replace('TABLE', table.name)" 
                                    class="flex items-center px-3 py-2 rounded-md hover:bg-slate-800 hover:text-white transition text-xs group"
                                    :class="'{{ $table ?? '' }}' == table.name ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/20' : 'text-slate-400'"
                                >
                                    <i class="fa-solid fa-table w-4 text-center mr-2 opacity-50 group-hover:opacity-100 group-hover:text-indigo-400"></i>
                                    <span x-text="table.name" class="truncate flex-grow"></span>
                                    <span x-text="table.formatted_count" class="text-[9px] font-bold opacity-40 group-hover:opacity-100 ml-2 bg-slate-800 px-1.5 py-0.5 rounded text-slate-300"></span>
                                    <i class="fa-solid fa-chevron-right ml-2 text-[8px] opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-1 group-hover:translate-x-0"></i>
                                </a>
                            </template>
                        </div>
                        <div x-show="allTables.filter(t => t.name.toLowerCase().includes(tableSearch.toLowerCase())).length === 0" class="py-10 text-center text-[10px] text-slate-600 italic">
                            No tables found
                        </div>
                    </div>
                </div>
                <div class="h-6 flex-shrink-0"></div>

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
                    <div class="ml-4 h-6 w-px bg-slate-200"></div>
                    <div class="ml-4 flex items-center space-x-2">
                        <h2 class="text-sm font-bold text-slate-800">@yield('title')</h2>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    @if(!empty(config('database-controllers.password')))
                        <form action="{{ route('database-controllers.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg border border-transparent hover:border-rose-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 transition duration-300 group">
                                <span class="text-[11px] font-black uppercase tracking-widest group-hover:mr-1 transition-all">Logout</span>
                                <i class="fa-solid fa-arrow-right-from-bracket text-base transition-transform group-hover:translate-x-1"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6 md:p-8 flex-grow">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-emerald-50 border-emerald-500/50 border rounded-lg flex items-center text-emerald-800 shadow-sm animate-fadeIn">
                        <div class="w-8 h-8 bg-emerald-500 text-white rounded-full flex items-center justify-center mr-3 shadow-sm">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <span class="font-medium font-sm">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-rose-50 border-rose-500/50 border rounded-lg flex items-center text-rose-800 shadow-sm animate-fadeIn">
                        <div class="w-8 h-8 bg-rose-500 text-white rounded-full flex items-center justify-center mr-3 shadow-sm">
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
                    &copy; {{ date('Y') }} <span class="font-bold text-indigo-600">Httpsnader1</span> - Database Controllers
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
