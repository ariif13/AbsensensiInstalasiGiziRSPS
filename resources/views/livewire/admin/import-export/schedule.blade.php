<div x-data="{ activeTab: 'import' }" class="space-y-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            {{ __('Schedule Data Management') }}
        </h3>

        <div class="flex w-full sm:w-auto justify-center bg-gray-200 dark:bg-gray-700 rounded-lg p-1">
            <button @click="activeTab = 'import'"
                :class="activeTab === 'import' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                class="px-4 py-1.5 text-sm font-medium rounded-md transition-all duration-200 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                {{ __('Import') }}
            </button>
            <button @click="activeTab = 'template'"
                :class="activeTab === 'template' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                class="px-4 py-1.5 text-sm font-medium rounded-md transition-all duration-200 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                {{ __('Template') }}
            </button>
        </div>
    </div>

    <div x-show="activeTab === 'import'" x-transition>
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-6">
                <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Import Monthly Shift Schedule') }}</h4>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">
                    {{ __('Use email or name to identify employee. Shift code: P=Pagi, S=Siang, M=Malam, OFF/L=Libur.') }}
                </p>
            </div>

            <form x-data="{ file: null, dragging: false }"
                @drop.prevent="dragging = false; file = $event.dataTransfer.files[0]; $refs.file.files = $event.dataTransfer.files; $wire.upload('file', file)"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                wire:submit.prevent="import" class="space-y-6">

                <div class="relative group">
                    <div :class="{'border-primary-500 bg-primary-50 dark:bg-primary-900/10': dragging, 'border-gray-300 dark:border-gray-600': !dragging}"
                         class="border-2 border-dashed rounded-xl p-8 text-center transition-all duration-200 cursor-pointer hover:border-primary-400 dark:hover:border-primary-500"
                         @click="$refs.file.click()">
                        <input type="file" class="hidden" x-ref="file" wire:model.live="file"
                               x-on:change="file = $refs.file.files[0]">

                        <template x-if="!file">
                            <div>
                                <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-primary-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Click to upload or drag and drop') }}</p>
                                <p class="text-xs text-gray-400 mt-1">XLSX, CSV (Max 10MB)</p>
                            </div>
                        </template>

                        <template x-if="file">
                            <div>
                                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white" x-text="file.name"></p>
                                <button type="button" @click.stop="file = null; $refs.file.value = null; $wire.set('file', null)" class="text-red-500 text-xs mt-2 hover:underline">{{ __('Remove file') }}</button>
                            </div>
                        </template>
                    </div>
                </div>

                @error('file')
                    <div class="p-2 text-sm text-red-600 bg-red-50 rounded-lg dark:bg-red-900/50 dark:text-red-400">{{ $message }}</div>
                @enderror

                <div x-show="file">
                    <x-danger-button class="w-full justify-center py-3" wire:loading.attr="disabled" wire:target="import">
                        {{ __('Submit Import') }}
                    </x-danger-button>
                </div>
            </form>

            @if($importResult)
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 p-4">
                        <p class="text-xs uppercase tracking-wider text-green-700 dark:text-green-300">{{ __('Imported') }}</p>
                        <p class="text-2xl font-bold text-green-800 dark:text-green-200">{{ $importResult['imported'] }}</p>
                    </div>
                    <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 p-4">
                        <p class="text-xs uppercase tracking-wider text-red-700 dark:text-red-300">{{ __('Skipped') }}</p>
                        <p class="text-2xl font-bold text-red-800 dark:text-red-200">{{ $importResult['skipped'] }}</p>
                    </div>
                </div>
            @endif

            @if(!empty($importErrors))
                <div class="mt-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                    <h4 class="text-sm font-bold text-red-800 dark:text-red-200 mb-2">{{ __('Import Errors') }}</h4>
                    <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300 max-h-52 overflow-y-auto space-y-1">
                        @foreach($importErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <div x-show="activeTab === 'template'" x-transition style="display: none;">
        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl p-6">
            <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ __('Download Schedule Template') }}</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                {{ __('Template columns: email, nama, bulan, tahun, lalu tanggal 1-31.') }}
            </p>

            <div class="text-sm text-gray-700 dark:text-gray-300 mb-5 space-y-1">
                <p>{{ __('Code Legend:') }}</p>
                <p><span class="font-semibold">P</span> = {{ __('Pagi') }}</p>
                <p><span class="font-semibold">S</span> = {{ __('Siang') }}</p>
                <p><span class="font-semibold">M</span> = {{ __('Malam') }}</p>
                <p><span class="font-semibold">OFF / L</span> = {{ __('Libur') }}</p>
            </div>

            @if($shifts->isNotEmpty())
                <div class="mb-5 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-3 py-2 text-left">{{ __('Shift') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('Time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shifts as $shift)
                                <tr class="border-t border-gray-100 dark:border-gray-700">
                                    <td class="px-3 py-2">{{ $shift->name }}</td>
                                    <td class="px-3 py-2">{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <x-button wire:click="downloadTemplate" class="w-full sm:w-auto">
                {{ __('Download Template') }}
            </x-button>
        </div>
    </div>

    @if($previewing && !empty($previewRows))
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <h4 class="text-sm font-bold uppercase tracking-wider text-gray-500">{{ __('Preview') }}</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left">{{ __('Name') }}</th>
                            <th class="px-4 py-2 text-left">{{ __('Email') }}</th>
                            <th class="px-4 py-2 text-left">{{ __('Code') }}</th>
                            <th class="px-4 py-2 text-left">{{ __('Shift') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($previewRows as $preview)
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td class="px-4 py-2">{{ $preview['date'] }}</td>
                                <td class="px-4 py-2">{{ $preview['user'] }}</td>
                                <td class="px-4 py-2">{{ $preview['email'] }}</td>
                                <td class="px-4 py-2">{{ $preview['code'] }}</td>
                                <td class="px-4 py-2">{{ $preview['shift'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
