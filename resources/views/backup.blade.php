@extends('database-controllers::layout')

@section('title', __('database-controllers::messages.backups'))

@section('content')
    <div class="space-y-8 animate-fadeIn" x-data="{
    showImportModal: false,
    showExportModal: false,
    showDeleteModal: false,
    showRestoreModal: false,
    showDeleteAllModal: false,
    showExcludeModal: false,
    deletingBackup: '',
    restoringBackup: '',
    isRestoring: false,
    isImporting: false,
    isUploading: false,
    uploadProgress: 0,
    selectedFile: null,
    selectedFileSize: 0,
    excludedRows: {{ json_encode($excludedTables) }},
    serverLimits: {{ json_encode($serverLimits) }},
    get isFileTooLarge() {
        if (!this.selectedFileSize) return false;
        const maxUpload = this.parseSize(this.serverLimits.upload_max_filesize);
        const maxPost = this.parseSize(this.serverLimits.post_max_size);
        const limit = Math.min(maxUpload, maxPost);
        return this.selectedFileSize > limit;
    },
    parseSize(sizeStr) {
        if (!sizeStr) return 0;
        const units = { 'K': 1024, 'M': 1024*1024, 'G': 1024*1024*1024 };
        const unit = sizeStr.slice(-1).toUpperCase();
        const val = parseFloat(sizeStr);
        return units[unit] ? val * units[unit] : val;
    },
    async handleImport(e) {
        if (this.isFileTooLarge) {
            e.preventDefault();
            this.isImporting = true;
            await this.uploadInChunks();
        } else {
            this.isImporting = true;
        }
    },
    async uploadInChunks() {
        const fileInput = this.$refs.sqlFileInput;
        const file = fileInput.files[0];
        if (!file) return;

        this.isUploading = true;
        this.uploadProgress = 0;
        const chunkSize = 5 * 1024 * 1024; // 5MB chunks
        const totalChunks = Math.ceil(file.size / chunkSize);
        const identifier = Math.random().toString(36).substring(2) + Date.now();

        for (let i = 0; i < totalChunks; i++) {
            const start = i * chunkSize;
            const end = Math.min(start + chunkSize, file.size);
            const chunk = file.slice(start, end);

            const formData = new FormData();
            formData.append('chunk', chunk);
            formData.append('index', i);
            formData.append('total', totalChunks);
            formData.append('identifier', identifier);
            formData.append('filename', file.name);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const response = await fetch('{{ route("database-controllers.backup.upload-chunk") }}', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    this.uploadProgress = Math.round(((i + 1) / totalChunks) * 100);
                    if (i === totalChunks - 1) {
                        this.finishImport(result.path);
                    }
                } else {
                    alert('Upload failed at chunk ' + (i + 1));
                    this.isImporting = false;
                    this.isUploading = false;
                    return;
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Upload error. Please check console.');
                this.isImporting = false;
                this.isUploading = false;
                return;
            }
        }
    },
    finishImport(serverPath) {
        const formData = new FormData();
        formData.append('sql_file_path', serverPath);
        formData.append('background', document.getElementById('background_import').checked ? '1' : '0');
        formData.append('_token', '{{ csrf_token() }}');

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("database-controllers.backup.import") }}';

        for (const [key, value] of formData.entries()) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    }
}">

        <!-- Action Header -->
        <div
            class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-6 overflow-hidden relative group">
            <div class="relative z-10">
                <h3 class="text-2xl font-black text-slate-800 mb-2">{{ __('database-controllers::messages.backup_management_title') }}</h3>
                <p class="text-sm text-slate-500 max-w-lg mb-6 leading-relaxed">
                    {{ __('database-controllers::messages.backup_management_desc') }}
                </p>
                <div class="flex items-center gap-5">
                    <button @click="showExportModal = true"
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/20 active:scale-95 group/btn">
                        <i class="fa-solid fa-cloud-arrow-up me-2 text-indigo-200 group-hover/btn:scale-110 transition cursor-pointer"></i>
                        {{ __('database-controllers::messages.generate_backup') }}
                    </button>
                    <button @click="showExcludeModal = true"
                            class="inline-flex items-center px-6 py-3 bg-white border border-rose-100 text-rose-600 rounded-xl text-sm font-bold hover:bg-rose-50 transition shadow-sm active:scale-95">
                        <i class="fa-solid fa-filter me-2 text-rose-300"></i>
                        {{ __('database-controllers::messages.excluded_tables') }}
                    </button>
                    <button @click="showImportModal = true"
                            class="inline-flex items-center px-6 py-3 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 transition shadow-sm active:scale-95">
                        <i class="fa-solid fa-file-import me-2 text-slate-400"></i>
                        {{ __('database-controllers::messages.import_sql') }}
                    </button>
                </div>
            </div>
            <div
                class="hidden lg:block w-64 h-64 bg-indigo-50/50 rounded-full absolute -end-20 -bottom-20 pointer-events-none transition-transform group-hover:scale-110"></div>
            <i class="fa-solid fa-database absolute -end-4 top-1/2 -translate-y-1/2 text-[200px] text-slate-50/10 pointer-events-none select-none"></i>
        </div>

        <!-- Backups List -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-800 flex items-center">
                    <i class="fa-solid fa-clock-rotate-left me-3 text-indigo-500"></i>
                    {{ __('database-controllers::messages.backup_history') }}
                </h3>
                <span
                    class="text-xs bg-indigo-100 text-indigo-700 font-bold px-3 py-1 rounded-full border border-indigo-200 shadow-sm">
                {{ count($backups) }} {{ __('database-controllers::messages.backups_found_count') }}
            </span>
                @if(count($backups) > 0)
                    <button @click="showDeleteAllModal = true"
                            class="ms-4 inline-flex items-center px-4 py-2 bg-rose-600 text-white rounded-lg text-xs font-bold hover:bg-rose-700 transition shadow-lg shadow-rose-600/20 active:scale-95">
                        <i class="fa-solid fa-trash-can me-2"></i>
                        {{ __('database-controllers::messages.delete_all_backups') }}
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto min-h-[300px]">
                <table class="w-full text-center">
                    <thead
                        class="bg-white border-b border-slate-100 text-[10px] uppercase text-slate-400 font-black tracking-widest">
                    <tr>
                        <th class="px-8 py-5">{{ __('database-controllers::messages.file_name') }}</th>
                        <th class="px-8 py-5">{{ __('database-controllers::messages.date_created') }}</th>
                        <th class="px-8 py-5">{{ __('database-controllers::messages.file_size') }}</th>
                        <th class="px-8 py-5 text-center">{{ __('database-controllers::messages.table_actions') }}</th>
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
                                <span
                                    class="px-3 py-1 bg-slate-100 text-slate-500 rounded-full text-[10px] font-black tracking-wider uppercase border border-slate-200">{{ $backup['size'] }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="restoringBackup = '{{ $backup['name'] }}'; showRestoreModal = true"
                                            class="w-10 h-10 text-emerald-500 hover:bg-emerald-100 rounded-lg transition"
                                            title="Restore Data">
                                        <i class="fa-solid fa-rotate-left text-lg"></i>
                                    </button>
                                    <a href="{{ route('database-controllers.backup.download', $backup['name']) }}"
                                       class="w-10 h-10 flex justify-center items-center text-indigo-500 hover:bg-indigo-100 rounded-lg transition"
                                       title="Download">
                                        <i class="fa-solid fa-download text-lg"></i>
                                    </a>
                                    <button @click="deletingBackup = '{{ $backup['name'] }}'; showDeleteModal = true"
                                            class="w-10 h-10 text-rose-500 hover:bg-rose-100 rounded-lg transition"
                                            title="Delete">
                                        <i class="fa-solid fa-trash-can text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div
                                        class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-cloud-arrow-up text-4xl text-slate-200"></i>
                                    </div>
                                    <h4 class="text-slate-400 font-bold uppercase tracking-widest text-xs">{{ __('database-controllers::messages.no_backups_found') }}</h4>
                                    <p class="text-slate-300 text-sm mt-1 max-w-sm">{{ __('database-controllers::messages.no_backups_found_desc') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODALS -->

        <!-- Export Modal -->
        <template x-teleport="body">
            <div x-show="showExportModal" x-cloak
                 class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden"
                     @click.away="showExportModal = false">
                    <div class="p-8">
                        <div
                            class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6 border border-indigo-100 shadow-sm">
                            <i class="fa-solid fa-file-export text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 mb-6 text-center">{{ __('database-controllers::messages.export_options') }}</h3>

                        <form action="{{ route('database-controllers.backup.export') }}" method="POST" @submit="isLoading = true">
                            @csrf
                            <div class="space-y-6">
                                <!-- Export Type -->
                                <div>
                                    <label
                                        class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">{{ __('database-controllers::messages.export_type') }}</label>
                                    <div class="grid grid-cols-1 gap-3">
                                        <label
                                            class="relative flex items-center p-4 border rounded-2xl cursor-pointer hover:bg-slate-50 transition"
                                            :class="true ? 'border-slate-200' : ''">
                                            <input type="radio" name="export_type" value="both" checked
                                                   class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                            <div class="ms-4">
                                                <p class="text-sm font-bold text-slate-800">{{ __('database-controllers::messages.structure_and_data') }}</p>
                                            </div>
                                        </label>
                                        <label
                                            class="relative flex items-center p-4 border rounded-2xl cursor-pointer hover:bg-slate-50 transition"
                                            :class="true ? 'border-slate-200' : ''">
                                            <input type="radio" name="export_type" value="structure"
                                                   class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                            <div class="ms-4">
                                                <p class="text-sm font-bold text-slate-800">{{ __('database-controllers::messages.structure_only') }}</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Format -->
                                <div>
                                    <label
                                        class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">{{ __('database-controllers::messages.export_format') }}</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label
                                            class="relative flex flex-col items-center p-4 border rounded-2xl cursor-pointer hover:bg-slate-50 transition">
                                            <input type="radio" name="format" value="sql" checked class="sr-only peer">
                                            <div class="w-full text-center peer-checked:text-indigo-600">
                                                <i class="fa-solid fa-file-code text-2xl mb-2"></i>
                                                <p class="text-xs font-bold uppercase tracking-tight">{{ __('database-controllers::messages.sql_format') }}</p>
                                            </div>
                                            <div
                                                class="absolute inset-0 border-2 border-transparent peer-checked:border-indigo-600 rounded-2xl pointer-events-none"></div>
                                        </label>
                                        <label
                                            class="relative flex flex-col items-center p-4 border rounded-2xl cursor-pointer hover:bg-slate-50 transition">
                                            <input type="radio" name="format" value="zip" class="sr-only peer">
                                            <div class="w-full text-center peer-checked:text-indigo-600">
                                                <i class="fa-solid fa-file-zipper text-2xl mb-2"></i>
                                                <p class="text-xs font-bold uppercase tracking-tight">{{ __('database-controllers::messages.zip_format') }}</p>
                                            </div>
                                            <div
                                                class="absolute inset-0 border-2 border-transparent peer-checked:border-indigo-600 rounded-2xl pointer-events-none"></div>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex flex-col space-y-3 pt-4">
                                    <button type="submit" @click="showExportModal = false"
                                            class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-600/20 active:scale-95 transition">
                                        {{ __('database-controllers::messages.generate_backup') }}
                                    </button>
                                    <button type="button" @click="showExportModal = false"
                                            class="w-full py-3 text-slate-400 font-bold hover:bg-slate-50 rounded-xl transition">
                                        {{ __('database-controllers::messages.cancel') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        <!-- Import Modal -->
        <template x-teleport="body">
            <div x-show="showImportModal" x-cloak
                 class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden"
                     @click.away="!isImporting && (showImportModal = false)">
                    <div class="p-8 text-center" x-show="!isImporting">
                        <div
                            class="w-16 h-16 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-6 border border-amber-100 shadow-sm">
                            <i class="fa-solid fa-file-import text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 mb-2">{{ __('database-controllers::messages.import_sql') }}</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-6"
                           x-html="'{{ __('database-controllers::messages.import_zip_sql') }}'.replace('.sql', '<span class=\'font-bold text-slate-800\'>.sql</span>').replace('.zip', '<span class=\'font-bold text-slate-800\'>.zip</span>')"></p>

                        <form action="{{ route('database-controllers.backup.import') }}" method="POST"
                              enctype="multipart/form-data" @submit="handleImport($event)">
                            @csrf
                            <div class="mb-6">
                                <label
                                    class="group relative flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-2xl cursor-pointer hover:bg-slate-50 transition-all"
                                    :class="selectedFile ? 'border-emerald-300 bg-emerald-50/30' : 'border-slate-200 hover:border-indigo-300'">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <template x-if="!selectedFile">
                                            <div class="text-center">
                                                <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-300 group-hover:text-indigo-400 mb-3 transition"></i>
                                                <p class="text-xs font-bold text-slate-400 group-hover:text-indigo-500">{{ __('database-controllers::messages.choose_sql_file') }}</p>
                                            </div>
                                        </template>
                                        <template x-if="selectedFile">
                                            <div class="text-center">
                                                <i class="fa-solid fa-file-circle-check text-3xl text-emerald-500 mb-3 animate-bounce"></i>
                                                <p class="text-sm font-black text-emerald-600 line-clamp-1 px-4"
                                                   x-text="selectedFile"></p>
                                                <p class="text-[9px] font-bold text-emerald-400 uppercase tracking-widest mt-1">{{ __('database-controllers::messages.ready_to_import') }}</p>
                                            </div>
                                        </template>
                                    </div>
                                    <input type="file" name="sql_file" class="hidden" required accept=".sql,.txt,.zip" x-ref="sqlFileInput"
                                           @change="selectedFile = $event.target.files[0].name; selectedFileSize = $event.target.files[0].size"/>
                                </label>

                                <template x-if="isFileTooLarge">
                                    <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl text-start">
                                        <p class="text-xs text-amber-700 font-bold mb-1">
                                            <i class="fa-solid fa-circle-info me-1"></i>
                                            Large file (<span x-text="(selectedFileSize / (1024*1024)).toFixed(2)"></span> MB) detected.
                                        </p>
                                        <p class="text-[10px] text-amber-600 leading-tight">
                                            PHP limits: upload_max_filesize=<span x-text="serverLimits.upload_max_filesize"></span>.
                                            We will use <b>Chunked Upload</b> to securely transfer this file without connection resets.
                                        </p>
                                    </div>
                                </template>
                            </div>

                            <div class="bg-rose-50 border border-rose-100 p-4 rounded-xl mb-6 text-center">
                                <div
                                    class="flex justify-center items-center text-rose-600 font-black text-[10px] uppercase tracking-widest mb-1">
                                    <i class="fa-solid fa-triangle-exclamation ltr:mr-2 rtl:ml-2"></i> {{ __('database-controllers::messages.critical_warning') }}
                                </div>
                                <p class="text-[10px] text-rose-500 leading-tight">
                                    {{ __('database-controllers::messages.import_overwrite_warning') }}
                                </p>
                            </div>

                            <div class="mb-6 flex items-center justify-center space-x-2">
                                <input type="checkbox" name="background" value="1" id="background_import" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                <label for="background_import" class="text-xs font-bold text-slate-500 cursor-pointer hover:text-indigo-600 transition">
                                    {{ __('database-controllers::messages.run_in_background') }}
                                </label>
                            </div>

                            <div class="flex flex-col space-y-3">
                                <button type="submit"
                                        class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-600/20 active:scale-95 transition">{{ __('database-controllers::messages.restore_now_btn') }}</button>
                                <button type="button" @click="showImportModal = false"
                                        class="w-full py-3 text-slate-400 font-bold hover:bg-slate-50 rounded-xl transition">{{ __('database-controllers::messages.cancel') }}</button>
                            </div>
                        </form>
                    </div>

                    <!-- Loading State -->
                    <div class="p-12 text-center" x-show="isImporting" x-cloak>
                        <template x-if="!isUploading">
                            <div class="inline-block animate-spin w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full mb-6"></div>
                        </template>
                        <template x-if="isUploading">
                            <div class="w-full bg-slate-100 rounded-full h-4 mb-6 relative overflow-hidden">
                                <div class="bg-indigo-600 h-full transition-all duration-300 shadow-sm" :style="'width: ' + uploadProgress + '%'"></div>
                                <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-white mix-blend-difference" x-text="uploadProgress + '%'"></span>
                            </div>
                        </template>
                        <h3 class="text-xl font-black text-slate-800 mb-2" x-text="isUploading ? 'Uploading Data...' : '{{ __('database-controllers::messages.restoring_data') }}'"></h3>
                        <p class="text-slate-500 text-sm" x-text="isUploading ? 'Please wait while the file is being uploaded in chunks.' : '{{ __('database-controllers::messages.restoring_data_desc') }}'"></p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Restore Confirmation Modal -->
        <template x-teleport="body">
            <div x-show="showRestoreModal" x-cloak
                 class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
                 @keydown.escape.window="!isRestoring && (showRestoreModal = false)">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden"
                     @click.away="!isRestoring && (showRestoreModal = false)">
                    <div class="p-8 text-center" x-show="!isRestoring">
                        <div
                            class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 border border-emerald-100 shadow-sm animate-pulse-slow">
                            <i class="fa-solid fa-rotate-left text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 mb-2">{{ __('database-controllers::messages.restore_database_title') }}</h3>
                        <p class="text-slate-500 text-sm mb-6 leading-relaxed">
                            {{ __('database-controllers::messages.restore_database_about') }} <br>
                            <span class="font-black text-slate-800 font-mono text-[11px]"
                                  x-text="restoringBackup"></span>
                        </p>

                        <div class="bg-rose-50 border border-rose-100 p-4 rounded-xl mb-7 text-center">
                            <div
                                class="flex justify-center items-center text-rose-600 font-black text-[10px] uppercase tracking-widest mb-1">
                                <i class="fa-solid fa-triangle-exclamation ltr:mr-2 rtl:ml-2"></i> {{ __('database-controllers::messages.critical_action') }}
                            </div>
                            <p class="text-[10px] text-rose-500 leading-tight">
                                {{ __('database-controllers::messages.overwrite_warning') }}
                            </p>
                        </div>

                        <div class="flex flex-col space-y-2">
                            <form
                                :action="'{{ route('database-controllers.backup.restore', 'FILENAME') }}'.replace('FILENAME', restoringBackup)"
                                method="POST" class="w-full" @submit="isRestoring = true; isLoading = true">
                                @csrf
                                <div class="mb-5 flex items-center justify-center space-x-2">
                                    <input type="checkbox" name="background" value="1" id="background_restore" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                    <label for="background_restore" class="text-xs font-bold text-slate-500 cursor-pointer hover:text-indigo-600 transition">
                                        {{ __('database-controllers::messages.run_in_background') }}
                                    </label>
                                </div>

                                <button type="submit"
                                        class="w-full py-4 bg-emerald-600 text-white rounded-2xl font-bold hover:bg-emerald-700 transition shadow-lg shadow-emerald-600/20 active:scale-95">{{ __('database-controllers::messages.restore_confirm_btn') }}</button>
                            </form>
                            <button @click="showRestoreModal = false"
                                    class="w-full py-3 text-slate-400 font-bold hover:bg-slate-50 rounded-xl transition">{{ __('database-controllers::messages.changed_my_mind_btn') }}</button>
                        </div>
                    </div>

                    <!-- Restore Loading State -->
                    <div class="p-12 text-center" x-show="isRestoring" x-cloak>
                        <div
                            class="inline-block animate-spin w-12 h-12 border-4 border-emerald-600 border-t-transparent rounded-full mb-6"></div>
                        <h3 class="text-xl font-black text-slate-800 mb-2">Executing Restoration...</h3>
                        <p class="text-slate-500 text-sm">Please do not refresh or close this window. Your database is
                            being updated to the selected version.</p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Delete Confirmation Modal -->
        <template x-teleport="body">
            <div x-show="showDeleteModal" x-cloak
                 class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
                 @keydown.escape.window="showDeleteModal = false">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden"
                     @click.away="showDeleteModal = false">
                    <div class="p-8 text-center">
                        <div
                            class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6 border border-red-200">
                            <i class="fa-solid fa-trash-can text-3xl text-red-600"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 mb-2">{{ __('database-controllers::messages.delete_backup_title') }}</h3>
                        <p class="text-slate-500 text-sm mb-8 leading-relaxed"
                           x-html="'{{ __('database-controllers::messages.delete_backup_about', ['name' => '___NAME___']) }}'.replace('___NAME___', '<span class=\'font-black text-slate-800 font-mono text-[11px]\'>' + deletingBackup + '</span>')">
                        </p>
                        <div class="flex flex-col space-y-2">
                            <form
                                :action="'{{ route('database-controllers.backup.delete', 'FILENAME') }}'.replace('FILENAME', deletingBackup)"
                                method="POST" class="w-full" @submit="isLoading = true">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full py-4 bg-red-600 text-white rounded-2xl font-bold hover:bg-red-700 transition shadow-lg shadow-red-600/20 active:scale-95">{{ __('database-controllers::messages.delete_backup_permanently_btn') }}</button>
                            </form>
                            <button @click="showDeleteModal = false"
                                    class="w-full py-4 text-slate-400 font-bold hover:bg-slate-50 rounded-2xl transition">{{ __('database-controllers::messages.cancel') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Delete All Backups Confirmation Modal -->
        <template x-teleport="body">
            <div x-show="showDeleteAllModal" x-cloak
                 class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
                 @keydown.escape.window="showDeleteAllModal = false">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden"
                     @click.away="showDeleteAllModal = false">
                    <div class="p-8 text-center">
                        <div
                            class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 border border-rose-100 shadow-sm">
                            <i class="fa-solid fa-trash-can text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 mb-2">{{ __('database-controllers::messages.delete_all_backups_title') }}</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-8">
                            {{ __('database-controllers::messages.delete_all_backups_confirm') }}
                        </p>

                        <form action="{{ route('database-controllers.backup.delete-all') }}" method="POST" @submit="isLoading = true">
                            @csrf
                            @method('DELETE')
                            <div class="flex flex-col space-y-3">
                                <button type="submit"
                                        class="w-full py-4 bg-rose-600 text-white rounded-2xl font-bold shadow-lg shadow-rose-600/20 active:scale-95 transition">
                                    {{ __('database-controllers::messages.delete_all_confirm_btn') }}
                                </button>
                                <button type="button" @click="showDeleteAllModal = false"
                                        class="w-full py-3 text-slate-400 font-bold hover:bg-slate-50 rounded-2xl transition">
                                    {{ __('database-controllers::messages.cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        <!-- Exclude Tables Modal -->
        <template x-teleport="body">
            <div x-show="showExcludeModal" x-cloak
                 class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
                 @keydown.escape.window="showExcludeModal = false">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden"
                     @click.away="showExcludeModal = false">
                    <div class="p-8">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-2xl font-black text-slate-800 tracking-tight">{{ __('database-controllers::messages.excluded_tables') }}</h3>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">{{ __('database-controllers::messages.backup_filter_system') }}</p>
                            </div>
                            <button @click="showExcludeModal = false"
                                    class="text-slate-400 hover:text-slate-600 transition">
                                <i class="fa-solid fa-times text-xl"></i>
                            </button>
                        </div>

                        <div class="bg-indigo-50/50 border border-indigo-100 p-4 rounded-xl mb-8 flex items-start">
                            <div
                                class="w-8 h-8 bg-indigo-500 text-white rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fa-solid fa-info text-xs"></i>
                            </div>
                            <div class="ltr:ml-4 rtl:mr-4">
                                <p class="text-xs font-bold text-indigo-900 mb-1 leading-none">{{ __('database-controllers::messages.why_exclude_tables') }}</p>
                                <p class="text-[11px] text-indigo-600 leading-relaxed"
                                   x-html="'{{ __('database-controllers::messages.exclude_tables_desc') }}'.replace('mysqldump', '<strong>mysqldump</strong>')">
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('database-controllers.backup.exclude-tables') }}" method="POST" @submit="isLoading = true">
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
                                                    placeholder="{{ __('database-controllers::messages.enter_table_name') }}"
                                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 pl-11 pr-4 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                                                >
                                            </div>
                                            <button type="button" @click="excludedRows.splice(index, 1)"
                                                    class="p-3 text-slate-300 hover:text-rose-500 transition-colors">
                                                <i class="fa-solid fa-circle-minus text-xl"></i>
                                            </button>
                                        </div>
                                    </template>

                                    <div x-show="excludedRows.length === 0"
                                         class="py-12 text-center border-2 border-dashed border-slate-100 rounded-2xl bg-slate-50/50">
                                        <i class="fa-solid fa-filter-circle-xmark text-4xl text-slate-200 mb-3 block"></i>
                                        <p class="text-sm font-bold text-slate-400">{{ __('database-controllers::messages.no_tables_excluded') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <button type="button" @click="excludedRows.push('')"
                                        class="flex-grow py-4 border-2 border-dashed border-indigo-200 text-indigo-500 rounded-2xl font-bold text-sm hover:bg-indigo-50 hover:border-indigo-300 transition flex items-center justify-center group active:scale-[0.98]">
                                    <i class="fa-solid fa-plus me-2 group-hover:rotate-90 transition-transform"></i> {{ __('database-controllers::messages.add_table_filter') }}
                                </button>
                                <button type="submit"
                                        class="px-8 py-4 bg-slate-900 text-white rounded-2xl font-black text-sm shadow-xl shadow-slate-900/20 hover:bg-slate-800 transition active:scale-[0.98]">
                                    {{ __('database-controllers::messages.save_exclusions') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

    </div>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out forwards;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
@endsection
