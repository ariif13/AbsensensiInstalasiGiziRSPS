<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-6 relative overflow-hidden">
    {{-- Decorative Blot --}}
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-indigo-50 dark:bg-indigo-900/20 rounded-full blur-2xl opacity-50 pointer-events-none"></div>

    <div class="flex flex-col items-center justify-center gap-6 relative z-10">
        {{-- Header Status --}}
        <div class="text-center">
            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                {{ $hasCheckedIn ? __('You\'re Checked In!') : __('Ready to Work?') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $hasCheckedIn 
                    ? __('Don\'t forget to clock out when you\'re done.') 
                    : __('Please clock in to start your shift.') }}
            </p>
        </div>

        {{-- Big Action Buttons --}}
        <div class="grid grid-cols-2 gap-4 w-full">
            {{-- Clock In Button --}}
            <a href="{{ route('scan') }}" 
               class="flex flex-col items-center justify-center p-4 rounded-xl transition-all duration-200 group relative overflow-hidden
               {{ $hasCheckedIn 
                  ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-60' 
                  : 'bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/40 border border-indigo-100 dark:border-indigo-800' }}">
                
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3 transition-colors
                    {{ $hasCheckedIn 
                       ? 'bg-gray-200 text-gray-400 dark:bg-gray-600 dark:text-gray-500' 
                       : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-800 dark:text-indigo-300 group-hover:bg-indigo-200 dark:group-hover:bg-indigo-700' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <span class="font-bold text-sm {{ $hasCheckedIn ? 'text-gray-400' : 'text-indigo-700 dark:text-indigo-300' }}">
                    {{ __('Clock In') }}
                </span>
            </a>

            {{-- Clock Out Button --}}
            <a href="{{ route('scan') }}" 
               class="flex flex-col items-center justify-center p-4 rounded-xl transition-all duration-200 group relative overflow-hidden
               {{ !$hasCheckedIn || $hasCheckedOut
                  ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-60' 
                  : 'bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20 dark:hover:bg-orange-900/40 border border-orange-100 dark:border-orange-800' }}">
                
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3 transition-colors
                    {{ !$hasCheckedIn || $hasCheckedOut
                       ? 'bg-gray-200 text-gray-400 dark:bg-gray-600 dark:text-gray-500' 
                       : 'bg-orange-100 text-orange-600 dark:bg-orange-800 dark:text-orange-300 group-hover:bg-orange-200 dark:group-hover:bg-orange-700' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <span class="font-bold text-sm {{ !$hasCheckedIn || $hasCheckedOut ? 'text-gray-400' : 'text-orange-700 dark:text-orange-300' }}">
                    {{ __('Clock Out') }}
                </span>
            </a>
        </div>
    </div>
</div>
