<div class="grid grid-cols-4 gap-4 mb-6">
    {{-- Check In/Out (handled by main scan component, but this is a shortcut if needed, or just visual consistency) --}}
    {{-- For now, let's link to relevant pages --}}
    
    <a href="{{ route('attendance-history') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30 group-hover:scale-105 transition-transform">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Riwayat') }}</span>
    </a>

    <a href="{{ route('apply-leave') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/30 group-hover:scale-105 transition-transform">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Izin/Sakit') }}</span>
    </a>

    {{-- Schedule (Coming Soon / Disabled for now as no user view exists) --}}
    <div class="flex flex-col items-center gap-2 group opacity-50 cursor-not-allowed" title="Coming Soon">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-violet-500 to-violet-600 flex items-center justify-center text-white shadow-lg shadow-violet-500/30">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </div>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Jadwal') }}</span>
    </div>

     <a href="{{ route('profile.show') }}" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-lg shadow-amber-500/30 group-hover:scale-105 transition-transform">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
        </div>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-300 text-center leading-tight">{{ __('Profil') }}</span>
    </a>
</div>
