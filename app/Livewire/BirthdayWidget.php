<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Component;

class BirthdayWidget extends Component
{
    public function render()
    {
        $today = Carbon::today();
        $upcomingBirthdays = User::whereNotNull('birth_date')
            ->get()
            ->map(function ($user) use ($today) {
                $birthday = Carbon::parse($user->birth_date)->setYear($today->year);
                if ($birthday->isPast() && !$birthday->isToday()) {
                    $birthday->addYear();
                }
                $user->next_birthday = $birthday;
                $user->days_until = $today->diffInDays($birthday, false);
                return $user;
            })
            ->filter(fn ($user) => $user->days_until >= 0 && $user->days_until <= 7)
            ->sortBy('days_until')
            ->take(5);

        return view('livewire.birthday-widget', [
            'birthdays' => $upcomingBirthdays,
        ]);
    }
}
