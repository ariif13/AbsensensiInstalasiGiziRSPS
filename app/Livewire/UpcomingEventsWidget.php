<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class UpcomingEventsWidget extends Component
{
    public function render()
    {
        $today = Carbon::today();

        $events = Cache::remember(
            'home:upcoming-events:'.Auth::id().':'.$today->format('Y-m-d'),
            now()->addSeconds(45),
            function () use ($today) {
                $announcements = Announcement::visibleForUser(Auth::id())
                    ->take(3)
                    ->get();

                $twoWeeksLater = $today->copy()->addDays(14);
                $holidays = Holiday::whereBetween('date', [$today->format('Y-m-d'), $twoWeeksLater->format('Y-m-d')])
                    ->orderBy('date', 'asc')
                    ->get();

                $nextWeek = $today->copy()->addDays(7);
                $birthdays = User::query()
                    ->where('group', 'user')
                    ->whereNotNull('birth_date')
                    ->select(['id', 'name', 'profile_photo_path', 'birth_date'])
                    ->get()
                    ->filter(function ($user) use ($today, $nextWeek) {
                        $birthday = Carbon::parse($user->birth_date)->year($today->year);
                        if ($birthday->isPast() && !$birthday->isToday()) {
                            $birthday->addYear();
                        }

                        return $birthday->between($today, $nextWeek);
                    })
                    ->sortBy(function ($user) use ($today) {
                        $birthday = Carbon::parse($user->birth_date)->year($today->year);
                        if ($birthday->isPast() && !$birthday->isToday()) {
                            $birthday->addYear();
                        }
                        return $birthday->timestamp;
                    })
                    ->take(5)
                    ->values();

                return [
                    'announcements' => $announcements,
                    'holidays' => $holidays,
                    'birthdays' => $birthdays,
                ];
            }
        );

        $announcements = $events['announcements'];
        $holidays = $events['holidays'];
        $birthdays = $events['birthdays'];

        // Determine active tab or state
        $hasEvents = $announcements->isNotEmpty() || $holidays->isNotEmpty() || $birthdays->isNotEmpty();

        return view('livewire.upcoming-events-widget', [
            'announcements' => $announcements,
            'holidays' => $holidays,
            'birthdays' => $birthdays,
            'hasEvents' => $hasEvents,
        ]);
    }
}
