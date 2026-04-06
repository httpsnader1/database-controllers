@extends('database-controllers::layout')

@section('title', 'Database Backups')

@section('content')
<div class="space-y-8 animate-fadeIn" x-data="{
    showImportModal: false,
    showDeleteModal: false,
    showRestoreModal: false,
    showExcludeModal: false,
    deletingBackup: '',
    restoringBackup: '',
    isRestoring: false,
    isImporting: false,
    selectedFile: null,
    excludedRows: {{ json_encode($excludedTables) }}
}">

    <!-- Action Header -->
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-6 overflow-hidden relative group">
        <div class="relative z-10">
            <h3 class="text-2xl font-black text-slate-800 mb-2">Backups Management</h3>
            <p class="text-sm text-slate-500 max-w-lg mb-6 leading-relaxed">
                Regularly back up your database to keep your data safe. You can easily export full SQL dumps or restore your data from existing backup files.
            </p>
            <div class="flex items-center space-x-4">
                <form action="{{ route('database-controllers.backup.export') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/20 active:scale-95 group/btn">
                        <i class="fa-solid fa-cloud-arrow-up mr-2 text-indigo-200 group-hover/btn:scale-110 transition cursor-pointer"></i>
                        Generate New Backup
                    </button>
                </form>
                <button @click="showExcludeModal = true" class="inline-flex items-center px-6 py-3 bg-white border border-rose-100 text-rose-600 rounded-xl text-sm font-bold hover:bg-rose-50 transition shadow-sm active:scale-95">
                    <i class="fa-solid fa-filter mr-2 text-rose-300"></i>
                    Excluded Tables
                </button>
                <button @click="showImportModal = true" class="inline-flex items-center px-6 py-3 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 transition shadow-sm active:scale-95">
                    <i class="fa-solid fa-file-import mr-2 text-slate-400"></i>
                    Import SQL File
                </button>
            </div>
        </div>
        <div class="hidden lg:block w-64 h-64 bg-indigo-50/50 rounded-full absolute -right-20 -bottom-20 pointer-events-none transition-transform group-hover:scale-110"></div>
        <i class="fa-solid fa-database absolute -right-4 top-1/2 -translate-y-1/2 text-[200px] text-slate-50/10 pointer-events-none select-none"></i>
    </div>

    <!-- Backups List -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800 flex items-center">
                <i class="fa-solid fa-clock-rotate-left mr-3 text-indigo-500"></i>
                Backup History
            </h3>
            <span class="text-xs bg-indigo-100 text-indigo-700 font-bold px-3 py-1 rounded-full border border-indigo-200 shadow-sm">
                {{ count($backups) }} Backups Found
            </span>
        </div>

        <div class="overflow-x-auto min-h-[300px]">
             <table class="w-full text-center">
                <thead class="bg-white border-b border-slate-100 text-[10px] uppercase text-slate-400 font-black tracking-widest">
                    <tr>
                        <th class="px-8 py-5">File Name</th>
                        <th class="px-8 py-5">Date Created</th>
                        <th class="px-8 py-5">File Size</th>
                        <th class="px-8 py-5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($backups as $backup)
                        <tr class="hover:bg-slate-50/80 transition-all duration-200 group">
                            <td class="px-8 py-6">
                                <span class="text-xs font-bold text-slate-600 block">{{ $backup['name'] }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-xs font-bold text-slate-600 block">{{ $backup['date'] }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-full text-[10px] font-black tracking-wider uppercase border border-slate-200">{{ $backup['size'] }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center justify-center space-x-3">
                                    <button @click="restoringBackup = '{{ $backup['name'] }}'; showRestoreModal = true" class="p-2 text-emerald-500 hover:bg-emerald-100 rounded-lg transition" title="Restore Data">
                                        <i class="fa-solid fa-rotate-left text-lg"></i>
                                    </button>
                                    <a href="{{ route('database-controllers.backup.download', $backup['name']) }}" class="p-2 text-indigo-500 hover:bg-indigo-100 rounded-lg transition" title="Download">
                                        <i class="fa-solid fa-download text-lg"></i>
                                    </a>
                                    <button @click="deletingBackup = '{{ $backup['name'] }}'; showDeleteModal = true" class="p-2 text-rose-500 hover:bg-rose-100 rounded-lg transition" title="Delete">
                                        <i class="fa-solid fa-trash-can text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-cloud-arrow-up text-4xl text-slate-200"></i>
                                    </div>
                                    <h4 class="text-slate-400 font-bold uppercase tracking-widest text-xs">No Backups Yet</h4>
                                    <p class="text-slate-300 text-sm mt-1 max-w-sm">Generate your first backup by clicking the button at the top.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODALS -->

    <!-- Import Modal -->
    <template x-teleport="body">
        <div x-show="showImportModal" x-cloak class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden" @click.away="!isImporting && (showImportModal = false)">
                 <div class="p-8 text-center" x-show="!isImporting">
                    <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-6 border border-amber-100 shadow-sm">
                        <i class="fa-solid fa-file-import text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2">Import SQL File</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-6">
                        Select a <span class="font-bold text-slate-800">.sql</span> file to restore your database.
                    </p>
                    
                    <form action="{{ route('database-controllers.backup.import') }}" method="POST" enctype="multipart/form-data" @submit="isImporting = true">
                        @csrf
                        <div class="mb-6">
                            <label class="group relative flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-2xl cursor-pointer hover:bg-slate-50 transition-all"
                                   :class="selectedFile ? 'border-emerald-300 bg-emerald-50/30' : 'border-slate-200 hover:border-indigo-300'">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <template x-if="!selectedFile">
                                        <div class="text-center">
                                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-300 group-hover:text-indigo-400 mb-3 transition"></i>
                                            <p class="text-xs font-bold text-slate-400 group-hover:text-indigo-500">CHOOSE SQL FILE</p>
                                        </div>
                                    </template>
                                    <template x-if="selectedFile">
                                        <div class="text-center">
                                            <i class="fa-solid fa-file-circle-check text-3xl text-emerald-500 mb-3 animate-bounce"></i>
                                            <p class="text-sm font-black text-emerald-600 line-clamp-1 px-4" x-text="selectedFile"></p>
                                            <p class="text-[9px] font-bold text-emerald-400 uppercase tracking-widest mt-1">Ready to Import</p>
                                        </div>
                                    </template>
                                </div>
                                <input type="file" name="sql_file" class="hidden" required accept=".sql,.txt" @change="selectedFile = $event.target.files[0].name" />
                            </label>
                        </div>

                        <div class="bg-rose-50 border border-rose-100 p-4 rounded-xl mb-6 text-center">
                            <div class="flex justify-center items-center text-rose-600 font-black text-[10px] uppercase tracking-widest mb-1">
                                <i class="fa-solid fa-triangle-exclamation mr-2"></i> Critical Warning
                            </div>
                            <p class="text-[10px] text-rose-500 leading-tight">
                                Restoring a database will **OVERWRITE** your current data. This action is permanent and cannot be undone.
                            </p>
                        </div>

                        <div class="flex flex-col space-y-3">
                            <button type="submit" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-600/20 active:scale-95 transition">Restore Database Now</button>
                            <button type="button" @click="showImportModal = false" class="w-full py-3 text-slate-400 font-bold hover:bg-slate-50 rounded-xl transition">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Loading State -->
                <div class="p-12 text-center" x-show="isImporting" x-cloak>
                    <div class="inline-block animate-spin w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full mb-6"></div>
                    <h3 class="text-xl font-black text-slate-800 mb-2">Restoring Data...</h3>
                    <p class="text-slate-500 text-sm">Please do not close this window. This process may take a minute depending on your SQL size.</p>
                </div>
            </div>
        </div>
    </template>

    <!-- Restore Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="showRestoreModal" x-cloak class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn" @keydown.escape.window="!isRestoring && (showRestoreModal = false)">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden" @click.away="!isRestoring && (showRestoreModal = false)">
                <div class="p-8 text-center" x-show="!isRestoring">
                    <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 border border-emerald-100 shadow-sm animate-pulse-slow">
                        <i class="fa-solid fa-rotate-left text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2">Restore Database?</h3>
                    <p class="text-slate-500 text-sm mb-6 leading-relaxed">
                        You are about to restore the database using: <br>
                        <span class="font-black text-slate-800 font-mono text-[11px]" x-text="restoringBackup"></span>
                    </p>

                    <div class="bg-rose-50 border border-rose-100 p-4 rounded-xl mb-7 text-center">
                        <div class="flex justify-center items-center text-rose-600 font-black text-[10px] uppercase tracking-widest mb-1">
                            <i class="fa-solid fa-triangle-exclamation mr-2"></i> Critical Action
                        </div>
                        <p class="text-[10px] text-rose-500 leading-tight">
                            This will **COMPLETELY OVERWRITE** your current database. All current unsaved changes will be lost forever.
                        </p>
                    </div>

                    <div class="flex flex-col space-y-2">
                        <form :action="'{{ route('database-controllers.backup.restore', 'FILENAME') }}'.replace('FILENAME', restoringBackup)" method="POST" class="w-full" @submit="isRestoring = true">
                            @csrf
                            <button type="submit" class="w-full py-4 bg-emerald-600 text-white rounded-2xl font-bold hover:bg-emerald-700 transition shadow-lg shadow-emerald-600/20 active:scale-95">Yes, Restore Everything</button>
                        </form>
                        <button @click="showRestoreModal = false" class="w-full py-3 text-slate-400 font-bold hover:bg-slate-50 rounded-xl transition">I've changed my mind</button>
                    </div>
                </div>

                <!-- Restore Loading State -->
                <div class="p-12 text-center" x-show="isRestoring" x-cloak>
                    <div class="inline-block animate-spin w-12 h-12 border-4 border-emerald-600 border-t-transparent rounded-full mb-6"></div>
                    <h3 class="text-xl font-black text-slate-800 mb-2">Executing Restoration...</h3>
                    <p class="text-slate-500 text-sm">Please do not refresh or close this window. Your database is being updated to the selected version.</p>
                </div>
            </div>
        </div>
    </template>

    <!-- Delete Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn" @keydown.escape.window="showDeleteModal = false">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden" @click.away="showDeleteModal = false">
                <div class="p-8 text-center">
                    <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6 border border-red-200">
                        <i class="fa-solid fa-trash-can text-3xl text-red-600"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2">Delete Backup?</h3>
                    <p class="text-slate-500 text-sm mb-8 leading-relaxed">
                        You are about to delete <span class="font-black text-slate-800 font-mono text-[11px]" x-text="deletingBackup"></span>. 
                        This file will be permanently removed from your storage.
                    </p>
                    <div class="flex flex-col space-y-2">
                        <form :action="'{{ route('database-controllers.backup.delete', 'FILENAME') }}'.replace('FILENAME', deletingBackup)" method="POST" class="w-full">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full py-4 bg-red-600 text-white rounded-2xl font-bold hover:bg-red-700 transition shadow-lg shadow-red-600/20 active:scale-95">Yes, Delete Permanently</button>
                        </form>
                        <button @click="showDeleteModal = false" class="w-full py-4 text-slate-400 font-bold hover:bg-slate-50 rounded-2xl transition">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out forwards;
    }
    [x-cloak] { display: none !important; }
</style>
    <!-- Exclude Tables Modal -->
    <template x-teleport="body">
        <div x-show="showExcludeModal" x-cloak class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn" @keydown.escape.window="showExcludeModal = false">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden" @click.away="showExcludeModal = false">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-2xl font-black text-slate-800 tracking-tight">Excluded Tables</h3>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Backup Filter System</p>
                        </div>
                        <button @click="showExcludeModal = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="fa-solid fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="bg-indigo-50/50 border border-indigo-100 p-4 rounded-xl mb-8 flex items-start">
                        <div class="w-8 h-8 bg-indigo-500 text-white rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-info text-xs"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-xs font-bold text-indigo-900 mb-1 leading-none">Why exclude tables?</p>
                            <p class="text-[11px] text-indigo-600 leading-relaxed">
                                Tables listed here will be skipped during the <strong>mysqldump</strong> process. Use this for large logs, temporary caches, or session data to keep your backups lightweight.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('database-controllers.backup.exclude-tables') }}" method="POST">
                        @csrf
                        <div class="max-h-[300px] overflow-y-auto px-1 scrollbar-thin mb-8">
                            <div class="space-y-3">
                                <template x-for="(tableName, index) in excludedRows" :key="index">
                                    <div class="flex items-center space-x-3 group animate-fadeIn">
                                        <div class="flex-grow relative">
                                            <i class="fa-solid fa-table absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                            <input 
                                                type="text" 
                                                name="excluded_tables[]" 
                                                x-model="excludedRows[index]"
                                                placeholder="Enter table name..."
                                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 pl-11 pr-4 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                                            >
                                        </div>
                                        <button type="button" @click="excludedRows.splice(index, 1)" class="p-3 text-slate-300 hover:text-rose-500 transition-colors">
                                            <i class="fa-solid fa-circle-minus text-xl"></i>
                                        </button>
                                    </div>
                                </template>

                                <div x-show="excludedRows.length === 0" class="py-12 text-center border-2 border-dashed border-slate-100 rounded-2xl bg-slate-50/50">
                                    <i class="fa-solid fa-filter-circle-xmark text-4xl text-slate-200 mb-3 block"></i>
                                    <p class="text-sm font-bold text-slate-400">No tables excluded yet</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-4">
                            <button type="button" @click="excludedRows.push('')" class="flex-grow py-4 border-2 border-dashed border-indigo-200 text-indigo-500 rounded-2xl font-bold text-sm hover:bg-indigo-50 hover:border-indigo-300 transition flex items-center justify-center group active:scale-[0.98]">
                                <i class="fa-solid fa-plus mr-2 group-hover:rotate-90 transition-transform"></i> Add Table to Filter
                            </button>
                            <button type="submit" class="px-8 py-4 bg-slate-900 text-white rounded-2xl font-black text-sm shadow-xl shadow-slate-900/20 hover:bg-slate-800 transition active:scale-[0.98]">
                                Save Exclusions
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection
