@extends('database-controllers::layout')

@section('title', __('database-controllers::messages.table') . " : {$table}")

@section('content')
<div x-data="{
    showAddModal: false,
    showEditModal: false,
    showDeleteModal: false,
    showViewModal: false,
    showFilters: {{ count($filters) > 0 ? 'true' : 'false' }},
    viewingRow: {},
    renderValue(val) {
        if (val === null || val === undefined) return 'NULL';
        try {
            if (typeof val === 'string' && (val.startsWith('{') || val.startsWith('['))) {
                return JSON.stringify(JSON.parse(val), null, 4);
            }
            if (typeof val === 'object') {
                return JSON.stringify(val, null, 4);
            }
        } catch (e) {}
        return val;
    },
    editingRow: {},
    primaryKey: '{{ $primaryKey }}',
    columnTypes: {{ json_encode($columnTypes ?? []) }},
    showDeleteModal: false,
    showBulkDeleteModal: false,
    showTruncateModal: false,
    selectedIds: [],
    toggleAll() {
        if (this.selectedIds.length === this.rowsOnPage.length) {
            this.selectedIds = [];
        } else {
            this.selectedIds = this.rowsOnPage.map(r => String(r[this.primaryKey]));
        }
    },
    toggleOne(id) {
        id = String(id);
        if (this.selectedIds.includes(id)) {
            this.selectedIds = this.selectedIds.filter(i => String(i) !== id);
        } else {
            this.selectedIds.push(id);
        }
    },
    get rowsOnPage() {
        return {{ json_encode($rows->items()) }};
    },
    jsonFields: {},
    initJsonField(col, val = null) {
        if (this.jsonFields[col]) return;
        let initial = [];
        try {
            if (val && typeof val === 'string' && (val.startsWith('{') || val.startsWith('['))) {
                let parsed = JSON.parse(val);
                Object.keys(parsed).forEach(k => initial.push({key: k, value: parsed[k]}));
            } else if (val && typeof val === 'object') {
                Object.keys(val).forEach(k => initial.push({key: k, value: val[k]}));
            }
        } catch (e) {}
        if (initial.length === 0) initial.push({key: '', value: ''});
        this.jsonFields[col] = initial;
    },
    addJsonEntry(col) {
        this.jsonFields[col].push({key: '', value: ''});
    },
    removeJsonEntry(col, index) {
        this.jsonFields[col].splice(index, 1);
        if (this.jsonFields[col].length === 0) this.addJsonEntry(col);
    },
    getJsonString(col) {
        let obj = {};
        if (!this.jsonFields[col]) return '{}';
        this.jsonFields[col].forEach(item => {
            if (item.key) obj[item.key] = item.value;
        });
        return JSON.stringify(obj);
    },
    deletingId: null,
    filters: {{ json_encode($filters) }},
    operators: ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'IN', 'IS NULL', 'IS NOT NULL'],
    init() {
        this.$watch('showEditModal', (val) => {
            if (val) {
                // Clear existing jsonFields before re-init
                this.jsonFields = {};
                Object.keys(this.columnTypes).forEach(col => {
                    if (this.columnTypes[col] === 'json') {
                        this.initJsonField(col, this.editingRow[col]);
                    }
                });
            }
        });
        this.$watch('showAddModal', (val) => {
            if (val) {
                this.jsonFields = {};
                 Object.keys(this.columnTypes).forEach(col => {
                    if (this.columnTypes[col] === 'json') {
                        this.initJsonField(col, null);
                    }
                });
            }
        });
    }
}" class="space-y-6">

    <!-- Breadcrumbs and Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <nav class="flex items-center text-sm font-medium text-slate-500 whitespace-nowrap overflow-x-auto pb-2 md:pb-0">
            <a href="{{ route('database-controllers.index') }}" class="hover:text-indigo-600 flex items-center">
                <i class="fa-solid fa-home me-1 text-slate-400"></i> {{ __('database-controllers::messages.dashboard') }}
            </a>
            <i class="fa-solid fa-chevron-right rtl:rotate-180 mx-2 text-xs text-slate-300"></i>
            <span class="text-slate-800 font-bold bg-indigo-50 px-2 py-1 rounded border border-indigo-100 font-mono text-[11px] uppercase tracking-wider">{{ $table }}</span>
        </nav>

        <div class="flex items-center gap-3">
             <div class="flex items-center bg-white border border-slate-200 rounded-lg px-3 py-2 shadow-sm">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest me-2 border-e pe-2 border-slate-100">{{ __('database-controllers::messages.rows_per_page') }}</span>
                <select
                    class="bg-transparent text-sm font-bold text-slate-700 outline-none cursor-pointer pe-4"
                    @change="isLoading = true; let url = new URL(window.location.href); url.searchParams.set('per_page', $event.target.value); window.location.href = url.toString();"
                >
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" {{ (int)$perPage === (int)$option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
             </div>

             <button x-show="selectedIds.length > 0" @click="showBulkDeleteModal = true" class="inline-flex items-center justify-center px-4 py-2 bg-rose-50 border border-rose-200 text-rose-600 rounded-lg text-sm font-bold hover:bg-rose-100 transition shadow-sm active:scale-95 animate-fadeIn" x-transition>
                <i class="fa-solid fa-trash-can me-2 text-xs"></i>
                <span>{{ __('database-controllers::messages.delete_selected') }} (<span x-text="selectedIds.length"></span>)</span>
             </button>
             <button @click="showFilters = !showFilters" class="inline-flex items-center justify-center px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition shadow-sm active:scale-95" :class="showFilters ? 'ring-2 ring-indigo-500 border-indigo-200' : ''">
                <i class="fa-solid fa-filter me-2 text-xs" :class="showFilters ? 'text-indigo-600' : 'text-slate-400'"></i>
                <span>{{ __('database-controllers::messages.filters') }}</span>
                @if(count($filters) > 0)
                    <span class="ml-2 bg-indigo-600 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ count($filters) }}</span>
                @endif
            </button>
             <button @click="showAddModal = true" class="flex-1 md:flex-none inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition shadow-lg active:scale-95 shadow-indigo-200">
                <i class="fa-solid fa-plus me-2 text-xs"></i> {{ __('database-controllers::messages.add_row') }}
            </button>
            <button @click="showTruncateModal = true" class="flex-1 md:flex-none inline-flex items-center justify-center px-4 py-2 bg-rose-600 text-white rounded-lg text-sm font-bold hover:bg-rose-700 transition shadow-lg active:scale-95 shadow-rose-200">
                <i class="fa-solid fa-broom me-2 text-xs"></i> {{ __('database-controllers::messages.truncate_table') }}
            </button>
        </div>
    </div>

    <!-- Filtering Section -->
    <div x-show="showFilters" x-transition.origin.top.duration.400ms class="bg-white rounded-xl shadow-md border border-indigo-100 p-6 overflow-hidden">
        <form method="GET" action="{{ route('database-controllers.table.show', $table) }}" @submit="isLoading = true">
            <div class="flex items-center justify-between mb-4 border-b pb-3 border-slate-100">
                <h3 class="font-bold text-slate-800 flex items-center">
                    <i class="fa-solid fa-filter me-2 text-indigo-500"></i>
                    {{ __('database-controllers::messages.filters') }}
                </h3>
                <button type="button" @click="filters.push({column: '', operator: '=', value: ''})" class="text-indigo-600 text-xs font-bold flex items-center hover:bg-indigo-50 px-3 py-1.5 rounded-full border border-indigo-100 transition">
                    <i class="fa-solid fa-plus me-1 text-[10px]"></i> {{ __('database-controllers::messages.add_filter') }}
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(filter, index) in filters" :key="index">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-3 bg-slate-50 p-3 rounded-lg border border-slate-100 relative group animate-fadeIn">
                        <div class="w-full md:w-1/3">
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ms-1">{{ __('database-controllers::messages.column') }}</label>
                            <select :name="'filters['+index+'][column]'" x-model="filter.column" class="w-full text-sm bg-white border border-slate-200 rounded-md py-1.5 px-3 focus:ring-2 focus:ring-indigo-500 font-mono">
                                <option value="">{{ __('database-controllers::messages.select_column') }}</option>
                                @foreach($columns as $col)
                                    <option value="{{ $col }}">{{ $col }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-full md:w-1/4">
                             <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ms-1">{{ __('database-controllers::messages.operator') }}</label>
                            <select :name="'filters['+index+'][operator]'" x-model="filter.operator" class="w-full text-sm bg-white border border-slate-200 rounded-md py-1.5 px-3 focus:ring-2 focus:ring-indigo-500">
                                <template x-for="op in operators">
                                    <option :value="op" x-text="op" :selected="filter.operator == op"></option>
                                </template>
                            </select>
                        </div>
                        <div class="w-full md:w-1/3">
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ms-1">{{ __('database-controllers::messages.value') }}</label>
                            <input type="text" :name="'filters['+index+'][value]'" x-model="filter.value" class="w-full text-sm bg-white border border-slate-200 rounded-md py-1.5 px-3 focus:ring-2 focus:ring-indigo-500" placeholder="{{ __('database-controllers::messages.search_value') }}">
                        </div>
                        <button type="button" @click="filters.splice(index, 1)" class="md:mt-5 text-slate-400 hover:text-red-500 transition-colors">
                            <i class="fa-solid fa-circle-xmark text-lg"></i>
                        </button>
                    </div>
                </template>

                <div x-show="filters.length === 0" class="py-4 text-center text-slate-400 italic text-sm">
                    {{ __('database-controllers::messages.no_active_filters') }}
                </div>

                <div x-show="filters.length > 0" class="flex justify-end pt-4 gap-3 items-center" x-transition>
                    @if(count($filters) > 0)
                        <a href="{{ route('database-controllers.table.show', $table) }}" @click="isLoading = true" class="px-5 py-2 text-xs font-bold bg-rose-50 text-rose-500 border border-rose-100 rounded-lg hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all duration-300 uppercase tracking-widest active:scale-95">
                            {{ __('database-controllers::messages.clear_results') }}
                        </a>
                    @endif
                    <button type="submit" class="inline-flex items-center px-8 py-2 bg-slate-800 text-white rounded-lg text-sm font-bold hover:bg-slate-900 transition shadow-sm active:scale-95">
                        <i class="fa-solid fa-magnifying-glass me-2 text-xs"></i> {{ __('database-controllers::messages.apply_filters') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Data Section -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-bold tracking-wider sticky top-0 bg-white">
                    <tr>
                        <th class="px-6 py-4 w-10 text-center">
                            <input type="checkbox" @change="toggleAll()" :checked="selectedIds.length === rowsOnPage.length && rowsOnPage.length > 0" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600 transition cursor-pointer">
                        </th>
                        @foreach($columns as $col)
                            <th class="px-6 py-4 whitespace-nowrap font-mono border-r border-slate-100 last:border-0 text-center relative group p-0">
                                @php
                                    $isCurrentSort = ($sortBy == $col);
                                    $nextDir = ($isCurrentSort && $sortDir == 'asc') ? 'desc' : 'asc';
                                @endphp
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => $col, 'sort_dir' => $nextDir]) }}" @click="isLoading = true" class="flex items-center justify-center w-full h-full px-6 py-4 hover:bg-slate-100/50 transition-colors">
                                    <span class="{{ $isCurrentSort ? 'text-indigo-600 font-black' : 'text-slate-500' }}">{{ $col }}</span>
                                    <span class="ms-2 flex flex-col text-[8px]">
                                        <i class="fa-solid fa-caret-up {{ $isCurrentSort && $sortDir == 'asc' ? 'text-indigo-600 scale-125' : 'text-slate-300 opacity-0 group-hover:opacity-100' }}"></i>
                                        <i class="fa-solid fa-caret-down {{ $isCurrentSort && $sortDir == 'desc' ? 'text-indigo-600 scale-125' : 'text-slate-300 opacity-0 group-hover:opacity-100' }}"></i>
                                    </span>
                                </a>
                            </th>
                        @endforeach
                        <th class="px-6 py-4 text-center sticky end-0 border-s rtl:shadow-[4px_0_6px_-2px_rgba(0,0,0,0.05)] ltr:shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.05)] bg-slate-50 border-slate-200 uppercase font-bold text-xs tracking-wider">
                            {{ __('database-controllers::messages.table_actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr class="hover:bg-slate-50 transition-all duration-200 group cursor-pointer"
                            :class="selectedIds.includes('{{ $row->$primaryKey }}') ? 'bg-indigo-50/20' : ''"
                            @click="viewingRow = {{ json_encode($row) }}; showViewModal = true">
                            <td class="px-6 py-4 text-center border-l-4 transition-all duration-200"
                                :class="selectedIds.includes('{{ $row->$primaryKey }}') ? 'border-indigo-500' : 'border-transparent'"
                                @click.stop="">
                                <input type="checkbox" :checked="selectedIds.includes('{{ $row->$primaryKey }}')" @change="toggleOne('{{ $row->$primaryKey }}')" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600 transition cursor-pointer">
                            </td>
                            @foreach($columns as $col)
                                <td class="px-6 py-4 text-sm text-slate-600 border-r border-slate-50 last:border-0 text-center @if(strlen($row->$col ?? '') > 100) max-w-xs truncate @endif" title="{{ $row->$col ?? '' }}">
                                    @if(is_null($row->$col))
                                        <span class="text-slate-300 italic text-[10px]">NULL</span>
                                    @else
                                        {{ Str::limit($row->$col ?? '', 100) }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-6 py-4 text-center sticky end-0 border-s rtl:shadow-[4px_0_6px_-2px_rgba(0,0,0,0.05)] ltr:shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.05)] bg-white group-hover:bg-slate-50 transition border-slate-100" @click.stop="">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="viewingRow = {{ json_encode($row) }}; showViewModal = true" class="w-10 h-10 text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 rounded-md transition" title="{{ __('database-controllers::messages.view_details') }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button @click="editingRow = {{ json_encode($row) }}; showEditModal = true" class="w-10 h-10 text-indigo-400 hover:bg-indigo-100 rounded-md transition" title="{{ __('database-controllers::messages.edit') }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button @click="deletingId = '{{ $row->$primaryKey }}'; showDeleteModal = true" class="w-10 h-10 text-red-500 hover:bg-red-100 rounded-md transition" title="{{ __('database-controllers::messages.delete') }}">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fa-solid fa-database text-4xl text-slate-200 mb-3"></i>
                                    <p class="text-slate-400 font-medium italic">{{ __('database-controllers::messages.results') }} (0)</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination and Summary -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-xs font-bold text-slate-500">
                {{ __('database-controllers::messages.showing') }} <span class="text-indigo-600">{{ number_format($rows->firstItem()) }}</span>
                {{ __('database-controllers::messages.to') }} <span class="text-indigo-600">{{ number_format($rows->lastItem()) }}</span>
                {{ __('database-controllers::messages.of') }} <span class="text-slate-800">{{ number_format($rows->total()) }}</span> {{ __('database-controllers::messages.results') }}
            </div>
            <div>
                {{ $rows->onEachSide(1)->links('database-controllers::pagination') }}
            </div>
        </div>
    </div>

    <!-- MODALS -->

    <!-- View Row Modal -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @keydown.escape.window="showViewModal = false" style="margin-top: 0 !important;">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden animate-fadeIn" @click.away="showViewModal = false">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-indigo-600 text-white">
                <h3 class="text-xl font-bold flex items-center">
                    <i class="fa-solid fa-circle-info mr-3 text-indigo-300"></i>
                    {{ __('database-controllers::messages.record_details') }}
                </h3>
                <button @click="showViewModal = false" class="text-indigo-200 hover:text-white transition"><i class="fa-solid fa-times text-xl"></i></button>
            </div>
            <div class="p-0 overflow-y-auto flex-grow bg-slate-50/50">
                <table class="w-full text-sm">
                    <tbody>
                        @foreach($columns as $col)
                            <tr class="border-b border-slate-100 last:border-0 hover:bg-white transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-500 bg-slate-50 w-1/3 uppercase text-[10px] tracking-widest border-r border-slate-100">
                                    {{ $col }}
                                </td>
                                <td class="px-6 py-4 text-slate-800 font-mono text-xs break-all whitespace-pre-wrap leading-relaxed shadow-inner bg-slate-50/50" x-text="renderValue(viewingRow['{{ $col }}'])">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100 flex justify-end bg-white">
                <button @click="showViewModal = false" class="px-8 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition shadow-lg">{{ __('database-controllers::messages.close_details') }}</button>
            </div>
        </div>
    </div>

    <!-- Add Row Modal -->
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" @keydown.escape.window="showAddModal = false" style="margin-top: 0 !important;">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-fadeIn" @click.away="showAddModal = false">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-indigo-600 text-white">
                <h3 class="text-xl font-bold flex items-center"><i class="fa-solid fa-plus-circle mr-2"></i> {{ __('database-controllers::messages.add_new_record') }} <span class="mx-2 font-mono underline">{{ $table }}</span></h3>
                <button @click="showAddModal = false" class="text-indigo-200 hover:text-white transition"><i class="fa-solid fa-times text-xl"></i></button>
            </div>
            <form action="{{ route('database-controllers.table.store', $table) }}" method="POST" class="flex flex-col flex-grow overflow-hidden" @submit="isLoading = true">
                @csrf
                <div class="p-6 overflow-y-auto grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($columns as $col)
                        @if($col !== $primaryKey)
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1 font-mono">{{ $col }}</label>
                                @if($col === 'password')
                                    <input type="password" name="{{ $col }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition text-sm" placeholder="Set password...">
                                @elseif(($columnTypes[$col] ?? '') === 'json')
                                    <div x-init="initJsonField('{{ $col }}')" class="space-y-2 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                                        <template x-for="(entry, index) in jsonFields['{{ $col }}']">
                                            <div class="flex space-x-2">
                                                <input type="text" x-model="entry.key" class="w-1/3 bg-white border border-slate-200 rounded-md px-2 py-1.5 text-xs font-bold" placeholder="Key">
                                                <input type="text" x-model="entry.value" class="flex-grow bg-white border border-slate-200 rounded-md px-2 py-1.5 text-xs" placeholder="Value">
                                                <button type="button" @click="removeJsonEntry('{{ $col }}', index)" class="text-rose-400 hover:text-rose-600 px-1"><i class="fa-solid fa-times"></i></button>
                                            </div>
                                        </template>
                                        <button type="button" @click="addJsonEntry('{{ $col }}')" class="w-full py-1.5 mt-1 border-2 border-dashed border-slate-200 text-slate-400 hover:text-indigo-500 hover:border-indigo-200 rounded-lg text-[10px] font-bold uppercase transition">
                                            <i class="fa-solid fa-plus mr-1"></i> Add Entry
                                        </button>
                                        <input type="hidden" name="{{ $col }}" :value="getJsonString('{{ $col }}')">
                                    </div>
                                @else
                                    <input type="text" name="{{ $col }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition text-sm">
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="p-6 border-t border-slate-100 flex justify-end space-x-3 bg-slate-50">
                    <button type="button" @click="showAddModal = false" class="px-6 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition">{{ __('database-controllers::messages.cancel') }}</button>
                    <button type="submit" class="px-8 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition shadow-lg">{{ __('database-controllers::messages.save_record') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Row Modal -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" @keydown.escape.window="showEditModal = false" style="margin-top: 0 !important;">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-fadeIn" @click.away="showEditModal = false">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-indigo-700 text-white">
                <h3 class="text-xl font-bold flex items-center"><i class="fa-solid fa-pen-to-square mr-2"></i> {{ __('database-controllers::messages.edit_record') }} #<span x-text="editingRow['{{ $primaryKey }}']" class="ml-1"></span></h3>
                <button @click="showEditModal = false" class="text-indigo-200 hover:text-white transition"><i class="fa-solid fa-times text-xl"></i></button>
            </div>
            <form :action="'{{ route('database-controllers.table.update', [$table, 'ID']) }}'.replace('ID', editingRow['{{ $primaryKey }}'])" method="POST" class="flex flex-col flex-grow overflow-hidden" @submit="isLoading = true">
                @csrf
                @method('PUT')
                <div class="p-6 overflow-y-auto grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($columns as $col)
                        @if($col !== $primaryKey)
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1 font-mono">{{ $col }}</label>
                                @if($col === 'password')
                                     <input type="password" name="{{ $col }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition text-sm" placeholder="Leave empty to keep current password">
                                @elseif(($columnTypes[$col] ?? '') === 'json')
                                    <div x-init="initJsonField('{{ $col }}', editingRow['{{ $col }}'])" class="space-y-2 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                                        <template x-for="(entry, index) in jsonFields['{{ $col }}']">
                                            <div class="flex space-x-2">
                                                <input type="text" x-model="entry.key" class="w-1/3 bg-white border border-slate-200 rounded-md px-2 py-1.5 text-xs font-bold" placeholder="Key">
                                                <input type="text" x-model="entry.value" class="flex-grow bg-white border border-slate-200 rounded-md px-2 py-1.5 text-xs" placeholder="Value">
                                                <button type="button" @click="removeJsonEntry('{{ $col }}', index)" class="text-rose-400 hover:text-rose-600 px-1"><i class="fa-solid fa-times"></i></button>
                                            </div>
                                        </template>
                                        <button type="button" @click="addJsonEntry('{{ $col }}')" class="w-full py-1.5 mt-1 border-2 border-dashed border-slate-200 text-slate-400 hover:text-indigo-500 hover:border-indigo-200 rounded-lg text-[10px] font-bold uppercase transition">
                                            <i class="fa-solid fa-plus mr-1"></i> Add Entry
                                        </button>
                                        <input type="hidden" name="{{ $col }}" :value="getJsonString('{{ $col }}')">
                                    </div>
                                @else
                                    <input type="text" name="{{ $col }}" x-model="editingRow['{{ $col }}']" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition text-sm">
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="p-6 border-t border-slate-100 flex justify-end space-x-3 bg-slate-50">
                    <button type="button" @click="showEditModal = false" class="px-6 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition">{{ __('database-controllers::messages.cancel') }}</button>
                    <button type="submit" class="px-8 py-2 bg-indigo-700 text-white rounded-lg text-sm font-bold hover:bg-indigo-800 transition shadow-lg">{{ __('database-controllers::messages.update_changes') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" @keydown.escape.window="showDeleteModal = false" style="margin-top: 0 !important;">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden animate-fadeIn" @click.away="showDeleteModal = false">
            <div class="p-6 text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-triangle-exclamation text-3xl text-red-600"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">{{ __('database-controllers::messages.delete_record_title') }}</h3>
                <p class="text-slate-500 text-sm mb-6">{{ __('database-controllers::messages.delete_record_confirm') }}</p>
                <div class="flex flex-col space-y-2">
                    <form :action="'{{ route('database-controllers.table.destroy', [$table, 'ID']) }}'.replace('ID', deletingId)" method="POST" class="w-full" @submit="isLoading = true">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full py-3 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition shadow-lg">{{ __('database-controllers::messages.delete_confirm_btn') }}</button>
                    </form>
                    <button @click="showDeleteModal = false" class="w-full py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-lg transition">{{ __('database-controllers::messages.cancel') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="showBulkDeleteModal" x-cloak class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn" @keydown.escape.window="showBulkDeleteModal = false">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden" @click.away="showBulkDeleteModal = false">
                <div class="p-8 text-center">
                    <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 border border-rose-100 shadow-sm">
                        <i class="fa-solid fa-trash-can text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2">
                        {!! __('database-controllers::messages.bulk_delete_title', ['count' => '<span x-text="selectedIds.length"></span>']) !!}
                    </h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-8">
                        {!! __('database-controllers::messages.bulk_delete_confirm', [
                            'count' => '<span class="font-black text-slate-800" x-text="selectedIds.length"></span>',
                            'table' => '<span class="font-mono bg-slate-100 px-1 rounded">' . $table . '</span>',
                            'status' => '<span class="text-rose-600 font-bold">' . __('database-controllers::messages.permanent') . '</span>'
                        ]) !!}
                    </p>

                    <form action="{{ route('database-controllers.table.bulk-delete', $table) }}" method="POST" @submit="isLoading = true">
                        @csrf
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="ids[]" :value="id">
                        </template>

                        <div class="flex flex-col space-y-3">
                            <button type="submit" class="w-full py-4 bg-rose-600 text-white rounded-2xl font-bold shadow-lg shadow-rose-600/20 active:scale-95 transition">
                                {{ __('database-controllers::messages.bulk_delete_btn') }}
                            </button>
                            <button type="button" @click="showBulkDeleteModal = false" class="w-full py-3 text-slate-400 font-bold hover:bg-slate-50 rounded-2xl transition">
                                {{ __('database-controllers::messages.bulk_cancel_btn') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- Truncate Table Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="showTruncateModal" x-cloak class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn" @keydown.escape.window="showTruncateModal = false">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden" @click.away="showTruncateModal = false">
                <div class="p-8 text-center">
                    <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 border border-rose-100 shadow-sm">
                        <i class="fa-solid fa-broom text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2">
                        {!! __('database-controllers::messages.truncate_confirm_title', ['table' => '<span class="font-mono bg-slate-100 px-1 rounded">' . $table . '</span>']) !!}
                    </h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-8">
                        {!! __('database-controllers::messages.truncate_confirm_message', [
                            'table' => '<span class="font-mono bg-slate-100 px-1 rounded">' . $table . '</span>',
                            'status' => '<span class="text-rose-600 font-bold">' . __('database-controllers::messages.permanent') . '</span>'
                        ]) !!}
                    </p>

                    <form action="{{ route('database-controllers.table.truncate', $table) }}" method="POST" @submit="isLoading = true">
                        @csrf
                        <div class="flex flex-col space-y-3">
                            <button type="submit" class="w-full py-4 bg-rose-600 text-white rounded-2xl font-bold shadow-lg shadow-rose-600/20 active:scale-95 transition">
                                {{ __('database-controllers::messages.truncate_btn') }}
                            </button>
                            <button type="button" @click="showTruncateModal = false" class="w-full py-3 text-slate-400 font-bold hover:bg-slate-50 rounded-2xl transition">
                                {{ __('database-controllers::messages.bulk_cancel_btn') }}
                            </button>
                        </div>
                    </form>
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
</div>
@endsection
