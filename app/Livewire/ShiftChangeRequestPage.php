<?php

namespace App\Livewire;

use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftChangeRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftChangeRequestPage extends Component
{
    use WithPagination;

    public $showModal = false;
    public $date;
    public $requested_shift_id;
    public $reason;
    public $currentShiftId;
    public $currentShiftLabel;

    protected $rules = [
        'date' => 'required|date|after_or_equal:today',
        'requested_shift_id' => 'required|exists:shifts,id',
        'reason' => 'required|string|min:5',
    ];

    public function render()
    {
        $requests = ShiftChangeRequest::with(['currentShift', 'requestedShift', 'approvedBy'])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(10);

        $shifts = Shift::orderBy('start_time')->get();

        return view('livewire.shift-change-request-page', [
            'requests' => $requests,
            'shifts' => $shifts,
        ])->layout('layouts.app');
    }

    public function create()
    {
        $this->resetForm();
        $this->date = Carbon::today()->toDateString();
        $this->loadCurrentShiftForDate();
        $this->showModal = true;
    }

    public function close()
    {
        $this->showModal = false;
    }

    public function updatedDate()
    {
        $this->loadCurrentShiftForDate();
    }

    public function store()
    {
        $this->validate();

        $schedule = Schedule::with('shift')
            ->where('user_id', Auth::id())
            ->whereDate('date', $this->date)
            ->first();

        if (!$schedule || $schedule->is_off || !$schedule->shift) {
            $this->addError('date', __('No active shift schedule found on selected date.'));
            return;
        }

        if ((int) $schedule->shift_id === (int) $this->requested_shift_id) {
            $this->addError('requested_shift_id', __('Requested shift must be different from current shift.'));
            return;
        }

        $requestedShift = Shift::find($this->requested_shift_id);
        if (!$requestedShift) {
            $this->addError('requested_shift_id', __('Requested shift not found.'));
            return;
        }

        if ($schedule->shift->end_time === $requestedShift->start_time) {
            $this->addError('requested_shift_id', __('Consecutive forward shift change is not allowed (e.g. Morning to Afternoon).'));
            return;
        }

        $hasPending = ShiftChangeRequest::where('user_id', Auth::id())
            ->whereDate('date', $this->date)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            $this->addError('date', __('You already have a pending shift change request for this date.'));
            return;
        }

        ShiftChangeRequest::create([
            'user_id' => Auth::id(),
            'date' => $this->date,
            'current_shift_id' => $schedule->shift_id,
            'requested_shift_id' => $requestedShift->id,
            'reason' => $this->reason,
            'status' => 'pending',
        ]);

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', __('Shift change request submitted.'));
    }

    private function loadCurrentShiftForDate(): void
    {
        $this->currentShiftId = null;
        $this->currentShiftLabel = null;

        if (!$this->date) {
            return;
        }

        $schedule = Schedule::with('shift')
            ->where('user_id', Auth::id())
            ->whereDate('date', $this->date)
            ->first();

        if (!$schedule || $schedule->is_off || !$schedule->shift) {
            return;
        }

        $this->currentShiftId = $schedule->shift_id;
        $this->currentShiftLabel = sprintf(
            '%s (%s - %s)',
            $schedule->shift->name,
            Carbon::parse($schedule->shift->start_time)->format('H:i'),
            Carbon::parse($schedule->shift->end_time)->format('H:i')
        );
    }

    private function resetForm(): void
    {
        $this->reset(['date', 'requested_shift_id', 'reason', 'currentShiftId', 'currentShiftLabel']);
        $this->resetValidation();
    }
}
