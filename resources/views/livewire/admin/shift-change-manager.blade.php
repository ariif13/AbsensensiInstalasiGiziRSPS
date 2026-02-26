<div class="py-6 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Shift Change Requests') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Approve or reject employee shift change requests.') }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 w-full sm:w-auto">
                <select wire:model.live="statusFilter" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm">
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                    <option value="all">{{ __('All') }}</option>
                </select>
                <x-input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search employee...') }}" />
            </div>
        </div>

        @if (session()->has('success'))
            <div class="mb-4 rounded-xl bg-green-50 p-4 border border-green-100 dark:bg-green-900/20 dark:border-green-800 text-sm text-green-800 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Employee') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Date') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Shift Change') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($requests as $request)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <img class="h-10 w-10 rounded-full object-cover" src="{{ $request->user->profile_photo_url }}" alt="{{ $request->user->name }}">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $request->user->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $request->user->jobTitle->name ?? __('N/A') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">
                                    {{ $request->date->format('d M Y') }}
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">{{ $request->reason }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">
                                    <div>{{ $request->currentShift?->name }} ({{ optional($request->currentShift)->start_time ? \Carbon\Carbon::parse($request->currentShift->start_time)->format('H:i') : '-' }} - {{ optional($request->currentShift)->end_time ? \Carbon\Carbon::parse($request->currentShift->end_time)->format('H:i') : '-' }})</div>
                                    <div class="text-xs text-primary-600 dark:text-primary-400 mt-1">→ {{ $request->requestedShift?->name }} ({{ optional($request->requestedShift)->start_time ? \Carbon\Carbon::parse($request->requestedShift->start_time)->format('H:i') : '-' }} - {{ optional($request->requestedShift)->end_time ? \Carbon\Carbon::parse($request->requestedShift->end_time)->format('H:i') : '-' }})</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if ($request->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($request->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                    @if($request->approvedBy)
                                        <div class="text-[10px] text-gray-400 mt-1">{{ __('by') }} {{ $request->approvedBy->name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($request->status === 'pending')
                                        <div class="flex justify-end gap-2">
                                            <button wire:click="approve({{ $request->id }})" class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 p-2 rounded-lg transition-colors" title="{{ __('Approve') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            <button wire:click="reject({{ $request->id }})" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-2 rounded-lg transition-colors" title="{{ __('Reject') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs italic">{{ __('Processed') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ __('No shift change requests found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>
