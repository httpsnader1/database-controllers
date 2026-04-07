<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Controllers - {{ __('database-controllers::messages.login_btn') }}</title>
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
</head>
<body class="bg-slate-950 font-sans text-slate-200 min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    
    <!-- Background Effects -->
    <div class="absolute top-0 left-0 w-full h-full pointer-events-none overflow-hidden">
        <div class="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-indigo-600/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[50%] h-[50%] bg-blue-600/10 rounded-full blur-[120px]"></div>
    </div>

    <div class="w-full max-w-md relative z-10 animate-fadeIn">
        
        <!-- Logo Area -->
        <div class="text-center mb-10 group">
            <div class="w-16 h-16 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-500 shadow-2xl backdrop-blur-md">
                <i class="fa-solid fa-database text-indigo-400 text-2xl group-hover:text-indigo-300 transition"></i>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">Database <span class="text-indigo-400">Controllers</span></h1>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-2 px-2">{{ __('database-controllers::messages.login_subtitle') }}</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white/5 border border-white/10 backdrop-blur-xl p-8 rounded-[2rem] shadow-2xl">
            @if(session('error'))
                <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-400 text-xs font-bold flex items-center animate-shake">
                    <i class="fa-solid fa-circle-exclamation mr-3 text-sm"></i>
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('database-controllers.login.post') }}" method="POST">
                @csrf
                <div class="relative group mb-5">
                    <i class="fa-solid fa-key absolute start-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm group-focus-within:text-indigo-400 transition"></i>
                    <input 
                        type="password" 
                        name="password" 
                        required 
                        autofocus
                        placeholder="{{ __('database-controllers::messages.enter_password') }}"
                        class="w-full bg-slate-900/50 border border-white/5 rounded-2xl py-4 ps-12 pe-4 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder:text-slate-700 text-white"
                    >
                </div>

                <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl font-black text-sm uppercase tracking-widest transition-all shadow-xl shadow-indigo-600/20 active:scale-[0.98] flex items-center justify-center group/btn">
                    <span>{{ __('database-controllers::messages.login_btn') }}</span>
                    <i class="fa-solid fa-arrow-right-long ms-3 rtl:rotate-180 group-hover/btn:translate-x-2 rtl:group-hover/btn:-translate-x-2 transition-transform"></i>
                </button>
            </form>
        </div>

        <!-- Footer Info -->
        <p class="text-center mt-8 text-[10px] font-bold text-slate-600 uppercase tracking-widest">
            {{ __('database-controllers::messages.made_by') }} ❤️ <a href="https://github.com/httpsnader1" target="_blank" class="text-slate-400">Mohamed Nader</a>
        </p>

    </div>

    <style>
        [x-cloak] { display: none !important; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .animate-fadeIn { animation: fadeIn 0.8s cubic-bezier(0.23, 1, 0.32, 1) forwards; }
        .animate-shake { animation: shake 0.5s ease-in-out; }
    </style>
</body>
</html>
