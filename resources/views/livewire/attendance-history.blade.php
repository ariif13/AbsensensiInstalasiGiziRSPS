<div class="space-y-6">
    @pushOnce('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @endpushOnce

    {{-- Main Card --}}
    <div class="rounded-2xl border border-indigo-100 bg-white shadow-xl shadow-indigo-100/50 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none relative overflow-hidden transition-all">
        
        {{-- Decorative Blob --}}
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-indigo-50 dark:bg-indigo-900/20 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

        {{-- Header --}}
        <div class="p-4 sm:p-6 border-b border-indigo-50 dark:border-gray-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="p-1.5 bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </span>
                    {{ __('Attendance History') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-1">
                    {{ __('Click date to view details.') }}
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="w-full sm:w-32">
                    <x-tom-select id="selectedMonth" wire:model.live="selectedMonth" placeholder="{{ __('Month') }}"
                        :options="collect(range(1, 12))->map(fn($m) => ['id' => sprintf('%02d', $m), 'name' => Carbon\Carbon::create()->month($m)->translatedFormat('F')])->values()->toArray()" />
                </div>
                <div class="w-full sm:w-24">
                    <x-tom-select id="selectedYear" wire:model.live="selectedYear" placeholder="{{ __('Year') }}"
                        :options="collect(range(date('Y') - 5, date('Y') + 1))->map(fn($y) => ['id' => $y, 'name' => $y])->values()->toArray()" />
                </div>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="p-4 sm:p-6 relative z-10">
            {{-- Days Header --}}
            <div class="grid grid-cols-7 mb-3">
                @foreach ([__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')] as $index => $day)
                    <div class="text-center text-[10px] uppercase tracking-wider font-bold text-gray-400 dark:text-gray-500 py-2 {{ $index === 0 ? 'text-rose-500' : ($index === 5 ? 'text-emerald-600 dark:text-emerald-500' : '') }}">
                        {{ $day }}
                    </div>
                @endforeach
            </div>

            {{-- Calendar Dates --}}
            <div class="grid grid-cols-7 gap-1 sm:gap-2">
                @foreach ($dates as $date)
                    @php
                        $isCurrentMonth = $date->month == $currentMonth;
                        $isToday = $date->isToday();
                        $isWeekend = $date->isWeekend();
                        
                        // Check if this date is a holiday
                        $dateKey = $date->format('Y-m-d');
                        $holiday = $holidays[$dateKey] ?? null;
                        $isHoliday = !is_null($holiday);
                        
                        // Find attendance
                        $attendance = $attendances->firstWhere(fn($v, $k) => $v->date->isSameDay($date));
                        $status = ($attendance ?? [
                            'status' => $isWeekend || $isHoliday || !$date->isPast() ? '-' : 'absent',
                        ])['status'];

                        // Styles
                        // Use softer borders and backgrounds
                        $bgClass = $isCurrentMonth ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-900/30';
                        $textClass = $isCurrentMonth ? 'text-gray-700 dark:text-gray-200' : 'text-gray-300 dark:text-gray-600';
                        // Replace border with ring for cleaner look or very subtle border
                        $borderClass = $isToday ? 'ring-2 ring-indigo-500 z-10' : 'border border-gray-100 dark:border-gray-700/50';
                        
                        // Holiday styling (priority over weekend)
                        if ($isHoliday && $isCurrentMonth) {
                            $bgClass = 'bg-rose-50 dark:bg-rose-900/20';
                            $textClass = 'text-rose-600 dark:text-rose-400';
                            $borderClass = $isToday ? 'ring-2 ring-indigo-500 z-10' : 'border border-rose-100 dark:border-rose-900/30';
                        } elseif ($date->isSunday() && $isCurrentMonth) {
                            $textClass = 'text-rose-500 dark:text-rose-400';
                        } elseif ($date->isFriday() && $isCurrentMonth) {
                            $textClass = 'text-emerald-600 dark:text-emerald-400';
                        }

                        // Status Marker
                        $markerColor = match($status) {
                            'present' => 'bg-emerald-500 shadow-sm shadow-emerald-200 dark:shadow-none',
                            'late' => 'bg-amber-500 shadow-sm shadow-amber-200 dark:shadow-none',
                            'excused', 'sick' => match($attendance['approval_status'] ?? 'approved') {
                                'pending' => 'bg-amber-300 ring-2 ring-amber-100',
                                'rejected' => 'bg-rose-600 ring-2 ring-rose-200',
                                default => $status === 'excused' ? 'bg-sky-500' : 'bg-violet-500'
                            },
                            'absent' => 'bg-rose-500 shadow-sm shadow-rose-200 dark:shadow-none',
                            default => $isToday ? 'bg-indigo-500' : null // Blue dot for today if no status yet
                        };
                    @endphp

                    <div class="aspect-[1/1] sm:aspect-[4/3] group relative">
                        <button type="button"
                            @if($attendance) wire:click="show({{ $attendance['id'] }})" @endif
                            class="w-full h-full flex flex-col items-center justify-between p-1 sm:p-2 rounded-xl transition-all duration-200 {{ $bgClass }} {{ $textClass }} {{ $borderClass }} hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $attendance ? 'cursor-pointer hover:shadow-md hover:-translate-y-0.5' : 'cursor-default' }}">
                            
                            {{-- Holiday Indicator --}}
                            @if($isHoliday && $isCurrentMonth)
                                <span class="absolute top-1 right-1 text-[8px]" title="{{ $holiday->name }}">🔴</span>
                            @endif
                            
                            {{-- Date Number --}}
                            <span class="text-xs sm:text-sm font-semibold {{ !$isCurrentMonth ? 'opacity-40' : '' }}">
                                {{ $date->day }}
                            </span>
                            
                            {{-- Holiday Name (visible on desktop) --}}
                            @if($isHoliday && $isCurrentMonth)
                                <span class="hidden sm:block text-[9px] leading-tight text-rose-500 dark:text-rose-400 font-medium truncate max-w-full px-1">
                                    {{ Str::limit($holiday->name, 8) }}
                                </span>
                            @endif

                            {{-- Status Indicator --}}
                            @if($markerColor && $status !== '-')
                                <div class="mb-1">
                                    <span class="inline-flex h-2 w-2 rounded-full {{ $markerColor }}"></span>
                                    <span class="sr-only">{{ ucfirst($status) }}</span>
                                    
                                    {{-- Time for desktop (optional) --}}
                                    @if($attendance && isset($attendance['time_in']))
                                         <span class="hidden sm:inline-block text-[9px] text-gray-400 dark:text-gray-500 font-mono">
                                            {{ \App\Helpers::format_time($attendance['time_in']) }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
        
        {{-- Holidays List (like real calendar) --}}
        @if($holidays->isNotEmpty())
        <div class="px-4 py-3 sm:px-6 bg-rose-50/50 dark:bg-rose-900/10 border-t border-rose-100 dark:border-rose-700/30 backdrop-blur-sm">
            <h4 class="text-xs font-bold text-rose-600 dark:text-rose-400 mb-2 flex items-center gap-1 uppercase tracking-wide">
                <span class="text-lg">📅</span> {{ __('Holidays') }}
            </h4>
            <div class="flex flex-wrap gap-2">
                @foreach($holidays->sortBy(fn($h) => $h->date->day) as $holiday)
                    <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-rose-100/50 dark:bg-rose-800/30 border border-rose-200 dark:border-rose-700 text-xs text-rose-800 dark:text-rose-200">
                        <span class="font-bold">{{ $holiday->date->day }}</span>
                        <span>{{ $holiday->name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
        
        {{-- Summary Section (Integrated) --}}
        <div class="p-4 sm:p-6 border-t border-gray-100 dark:border-gray-700 relative z-10 bg-gray-50/30 dark:bg-gray-800/50">
            <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-4 uppercase tracking-wider">
                {{ __('Current Month Summary') }}
            </h4>
            
            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                {{-- Present --}}
                <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-3 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                         <svg class="w-8 h-8 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    </div>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $counts['present'] ?? 0 }}</p>
                    <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mt-1">{{ __('Present') }}</p>
                </div>
                
                {{-- Late --}}
                <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-3 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                         <svg class="w-8 h-8 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                    </div>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $counts['late'] ?? 0 }}</p>
                    <p class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider mt-1">{{ __('Late') }}</p>
                </div>
                
                {{-- Excused --}}
                <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-3 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                         <svg class="w-8 h-8 text-sky-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                    </div>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $counts['excused'] ?? 0 }}</p>
                    <p class="text-[10px] font-bold text-sky-600 dark:text-sky-400 uppercase tracking-wider mt-1">{{ __('Excused') }}</p>
                </div>
                
                {{-- Sick --}}
                <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-3 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                         <svg class="w-8 h-8 text-violet-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 100-2 1 1 0 000 2zm7-1a1 1 0 11-2 0 1 1 0 012 0zm-7.536 5.879a1 1 0 001.415 0 3 3 0 014.242 0 1 1 0 001.415-1.415 5 5 0 00-7.072 0 1 1 0 000 1.415z" clip-rule="evenodd"></path></svg>
                    </div>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $counts['sick'] ?? 0 }}</p>
                    <p class="text-[10px] font-bold text-violet-600 dark:text-violet-400 uppercase tracking-wider mt-1">{{ __('Sick') }}</p>
                </div>
                
                {{-- Absent --}}
                <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-3 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group col-span-2 sm:col-span-1">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                         <svg class="w-8 h-8 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    </div>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $counts['absent'] ?? 0 }}</p>
                    <p class="text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider mt-1">{{ __('Absent') }}</p>
                </div>
            </div>
        </div>
        
        {{-- Legenda Modern --}}
        <div class="px-5 py-2 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/50">
            <div class="flex flex-wrap justify-center gap-4 text-[10px] text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span> {{ __('Pending') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-rose-500"></span> {{ __('Rejected') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span> {{ __('Today') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Include Modal Component --}}
    @include('components.attendance-detail-modal')

    @stack('attendance-detail-scripts')
</div>
