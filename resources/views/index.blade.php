@extends('database-controllers::layout')

@section('title', __('database-controllers::messages.dashboard'))

@section('content')
<div class="animate-fadeIn grid grid-cols-1 gap-8 items-start">
        
    <!-- Left: Stats List (Each in a line) -->
     <div class="grid grid-cols-1 gap-4">
        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest ms-2">{{ __('database-controllers::messages.dashboard') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($stats as $label => $value)
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex items-center group hover:border-indigo-500/50 hover:shadow-md transition-all duration-300">
                    <div class="w-10 h-10 rounded-xl @if($loop->index == 0) bg-indigo-50 text-indigo-600 @elseif($loop->index == 1) bg-emerald-50 text-emerald-600 @elseif($loop->index == 2) bg-amber-50 text-amber-600 @else bg-rose-50 text-rose-600 @endif flex items-center justify-center me-4 group-hover:scale-110 transition-transform">
                        @if($loop->index == 0) <i class="fa-solid fa-table text-base"></i>
                        @elseif($loop->index == 1) <i class="fa-solid fa-list-check text-base"></i>
                        @elseif($loop->index == 2) <i class="fa-solid fa-hard-drive text-base"></i>
                        @else <i class="fa-solid fa-clock-rotate-left text-base"></i> @endif
                    </div>
                    <div class="flex-grow flex items-center justify-between">
                        <div>
                            <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider leading-none mb-1">{{ $label }}</p>
                            <p class="text-lg font-black text-slate-800 leading-none">{{ $value }}</p>
                        </div>
                        <div class="text-slate-100 group-hover:text-slate-200 transition">
                            <i class="fa-solid fa-chevron-right rtl:rotate-180"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Right: Connection Info Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden sticky top-24">
        <div class="p-6 bg-slate-50 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-800 flex items-center uppercase tracking-wider">
                <i class="fa-solid fa-circle-info me-3 text-indigo-500"></i>
                {{ __('database-controllers::messages.connection') }}
            </h3>
        </div>
        <div class="p-6 flex-grow">
            <div class="space-y-3">
                @foreach($dbInfo as $key => $value)
                    <div class="flex items-center justify-between py-2.5 border-b border-slate-50 last:border-0 hover:bg-slate-50 px-3 rounded-lg transition group">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ $key }}</span>
                        <span class="text-xs font-mono font-bold @if($key == __('database-controllers::messages.database')) text-indigo-600 bg-indigo-50 px-2 py-1 rounded @else text-slate-700 @endif group-hover:scale-105 transition-transform origin-start">
                            {{ $value }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

<style>
    .animate-fadeIn {
        animation: fadeIn 0.4s ease-out forwards;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection
