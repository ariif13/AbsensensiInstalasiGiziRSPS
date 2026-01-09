<?php

namespace App\Livewire;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HomeAttendanceStatus extends Component
{
    public $hasCheckedIn = false;
    public $hasCheckedOut = false;

    public function mount()
    {
        $this->checkAttendanceStatus();
    }

    public function checkAttendanceStatus()
    {
        $user = Auth::user();
        $today = now()->format('Y-m-d');
        
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            $this->hasCheckedIn = !is_null($attendance->time_in);
            $this->hasCheckedOut = !is_null($attendance->time_out);
        }
    }

    public function render()
    {
        return view('livewire.home-attendance-status');
    }
}
