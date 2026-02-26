<div class="py-6 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden relative">
            <div class="px-5 py-4 lg:px-8 lg:py-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 relative z-10">
                <div class="flex items-center gap-3">
                    @if($showModal)
                        <button wire:click="close" class="p-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                            <x-heroicon-o-arrow-left class="h-4 w-4 text-gray-500 dark:text-gray-300" />
                        </button>
                    @else
                        <x-secondary-button href="{{ route('home') }}" class="!rounded-xl !px-3 !py-2 border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600">
                            <x-heroicon-o-arrow-left class="h-4 w-4 text-gray-500 dark:text-gray-300" />
                        </x-secondary-button>
                    @endif
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        {{ $showModal ? __('New Shift Change') : __('Shift Change') }}
                    </h3>
                </div>

                @if(!$showModal)
                    <button wire:click="create" class="px-4 py-2 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-bold text-xs uppercase tracking-widest transition shadow-lg shadow-primary-500/30 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span class="hidden sm:inline">{{ __('New Request') }}</span>
                    </button>
                @endif
            </div>

            <div class="p-0">
                @if(session()->has('success'))
                    <div class="mx-6 mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                        {{ session('success') }}
                    </div>
                @endif

                @if($showModal)
                    <div class="p-6 lg:p-8">
                        <form wire:submit.prevent="store" class="space-y-6">
                            <div>
                                <x-label for="date" value="{{ __('Schedule Date') }}" />
                                <x-input id="date" type="date" class="mt-1 block w-full" wire:model.live="date" min="{{ now()->toDateString() }}" />
                                <x-input-error for="date" class="mt-2" />
                            </div>

                            <div>
                                <x-label value="{{ __('Current Shift') }}" />
                                <div class="mt-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    {{ $currentShiftLabel ?: __('No active shift schedule on selected date.') }}
                                </div>
                            </div>

                            <div>
                                <x-label for="requested_shift_id" value="{{ __('Requested Shift') }}" />
                                <select id="requested_shift_id" wire:model="requested_shift_id" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500">
                                    <option value="">-- {{ __('Select Shift') }} --</option>
                                    @foreach($shifts as $shift)
                                        @if((int) $shift->id !== (int) $currentShiftId)
                                            <option value="{{ $shift->id }}">
                                                {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <x-input-error for="requested_shift_id" class="mt-2" />
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Rule: forward consecutive changes are not allowed (e.g. Morning to Afternoon). Backward change is allowed.') }}
                                </p>
                            </div>

                            <div>
                                <x-label for="reason" value="{{ __('Reason') }}" />
                                <textarea id="reason" wire:model="reason" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" placeholder="{{ __('Example: family emergency, clinic duty adjustment, etc.') }}"></textarea>
                                <x-input-error for="reason" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <x-secondary-button wire:click="close" wire:loading.attr="disabled">
                                    {{ __('Cancel') }}
                                </x-secondary-button>

                                <x-button wire:loading.attr="disabled">
                                    {{ __('Submit Request') }}
                                </x-button>
                            </div>
                        </form>
                    </div>
                @else
                    @if($requests->isEmpty())
                        <div class="p-8 text-center flex flex-col items-center justify-center min-h-[360px]">
                            <div class="w-24 h-24 bg-gray-50 dark:bg-gray-700/50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ __('No Shift Change Requests') }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-sm">{{ __('You have not submitted any shift change requests yet.') }}</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($requests as $request)
                                <div class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ $request->date->format('d M Y') }}
                                            </h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $request->currentShift?->name }}
                                                ({{ optional($request->currentShift)->start_time ? \Carbon\Carbon::parse($request->currentShift->start_time)->format('H:i') : '-' }}
                                                - {{ optional($request->currentShift)->end_time ? \Carbon\Carbon::parse($request->currentShift->end_time)->format('H:i') : '-' }})
                                                <span class="mx-1">→</span>
                                                {{ $request->requestedShift?->name }}
                                                ({{ optional($request->requestedShift)->start_time ? \Carbon\Carbon::parse($request->requestedShift->start_time)->format('H:i') : '-' }}
                                                - {{ optional($request->requestedShift)->end_time ? \Carbon\Carbon::parse($request->requestedShift->end_time)->format('H:i') : '-' }})
                                            </p>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 italic">"{{ $request->reason }}"</p>
                                            @if($request->rejection_note)
                                                <p class="text-[11px] text-red-600 dark:text-red-400 mt-1">{{ __('Admin note:') }} {{ $request->rejection_note }}</p>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            @if($request->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300
                                            @elseif($request->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300
                                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300 @endif">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                            {{ $requests->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
