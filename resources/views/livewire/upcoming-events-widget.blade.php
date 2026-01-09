<div class="rounded-2xl border border-indigo-100 bg-white shadow-xl shadow-indigo-100/50 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none relative overflow-hidden transition-all"
    x-data="{ activeTab: 'all' }">

    {{-- Decorative Blob --}}
    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-indigo-50 dark:bg-indigo-900/20 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

    {{-- Header & Tabs --}}
    <div class="p-4 border-b border-indigo-50 dark:border-gray-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 relative z-10">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
            <span class="text-lg">📢</span>
            {{ __('What\'s Happening') }}
        </h3>
        
        <div class="flex p-1 bg-gray-100 dark:bg-gray-700/50 rounded-xl">
            <button @click="activeTab = 'all'" 
                :class="{ 'bg-white dark:bg-gray-600 text-indigo-600 dark:text-indigo-400 shadow-sm': activeTab === 'all', 'text-gray-500 dark:text-gray-400 hover:text-gray-700': activeTab !== 'all' }"
                class="px-3 py-1 text-[10px] font-bold rounded-lg transition-all">
                {{ __('All') }}
            </button>
            <button @click="activeTab = 'announcements'" 
                :class="{ 'bg-white dark:bg-gray-600 text-indigo-600 dark:text-indigo-400 shadow-sm': activeTab === 'announcements', 'text-gray-500 dark:text-gray-400 hover:text-gray-700': activeTab !== 'announcements' }"
                class="px-3 py-1 text-[10px] font-bold rounded-lg transition-all">
                {{ __('News') }}
            </button>
            <button @click="activeTab = 'events'" 
                :class="{ 'bg-white dark:bg-gray-600 text-indigo-600 dark:text-indigo-400 shadow-sm': activeTab === 'events', 'text-gray-500 dark:text-gray-400 hover:text-gray-700': activeTab !== 'events' }"
                class="px-3 py-1 text-[10px] font-bold rounded-lg transition-all">
                {{ __('Events') }}
            </button>
        </div>
    </div>

    <div class="p-4 relative z-10 min-h-[150px]">
        @if(!$hasEvents)
            <div class="flex flex-col items-center justify-center py-6 text-center">
                <div class="w-12 h-12 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                </div>
                <p class="text-xs text-gray-400">{{ __('No updates right now.') }}</p>
            </div>
        @else
            <div class="space-y-4">
                {{-- Announcements Section --}}
                @if($announcements->isNotEmpty())
                <div x-show="activeTab === 'all' || activeTab === 'announcements'" class="space-y-3 transition-all" x-transition>
                    @foreach($announcements as $announcement)
                        <div class="group relative bg-white dark:bg-gray-700/30 rounded-xl p-3 border border-gray-100 dark:border-gray-700/50 shadow-sm hover:shadow-md transition-all">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg {{ $announcement->priority === 'high' ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400' : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                        {{ $announcement->title }}
                                    </h4>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">
                                        {{ Str::limit(strip_tags($announcement->content), 80) }}
                                    </p>
                                    <span class="text-[9px] text-gray-400 mt-1 block">
                                        {{ $announcement->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif

                {{-- Holidays & Birthdays Combined Grid --}}
                <div x-show="activeTab === 'all' || activeTab === 'events'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 transition-all" x-transition>
                    
                    {{-- Holidays --}}
                    @if($holidays->isNotEmpty())
                        @foreach($holidays as $holiday)
                            <div class="flex items-center gap-3 p-3 bg-rose-50/50 dark:bg-rose-900/20 rounded-xl border border-rose-100 dark:border-rose-800/30">
                                <div class="shrink-0 flex flex-col items-center justify-center w-10 h-10 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-rose-100 dark:border-rose-800/30">
                                    <span class="text-[9px] font-bold text-rose-500 uppercase tracking-tighter leading-none mb-0.5">{{ $holiday->date->shortMonthName }}</span>
                                    <span class="text-sm font-black text-gray-900 dark:text-white leading-none">{{ $holiday->date->day }}</span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $holiday->name }}</p>
                                    <span class="text-[9px] font-medium text-rose-500 bg-rose-100 dark:bg-rose-900/50 px-1.5 py-0.5 rounded">{{ __('Holiday') }}</span>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    {{-- Birthdays --}}
                    @if($birthdays->isNotEmpty())
                        @foreach($birthdays as $user)
                            <div class="flex items-center gap-3 p-3 bg-amber-50/50 dark:bg-amber-900/20 rounded-xl border border-amber-100 dark:border-amber-800/30">
                                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-lg object-cover border border-amber-100 dark:border-amber-800/30">
                                <div>
                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate max-w-[120px]">{{ $user->name }}</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-xs">🎂</span>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($user->birth_date)->format('d M') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
