<?php

namespace App\Livewire\Admin;

use App\Models\Schedule;
use App\Models\ShiftChangeRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftChangeManager extends Component
{
    use WithPagination;

    public $statusFilter = 'pending';
    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function approve($id)
    {
        $request = ShiftChangeRequest::findOrFail($id);

        if ($request->status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($request) {
            Schedule::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'date' => $request->date,
                ],
                [
                    'shift_id' => $request->requested_shift_id,
                    'is_off' => false,
                ]
            );

            $request->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_note' => null,
            ]);
        });

        session()->flash('success', __('Shift change approved.'));
    }

    public function reject($id)
    {
        $request = ShiftChangeRequest::findOrFail($id);

        if ($request->status !== 'pending') {
            return;
        }

        $request->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_note' => __('Rejected by admin.'),
        ]);

        session()->flash('success', __('Shift change rejected.'));
    }

    public function render()
    {
        $requests = ShiftChangeRequest::with(['user.jobTitle', 'currentShift', 'requestedShift', 'approvedBy'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->whereHas('user', function ($userQuery) {
                    $userQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.admin.shift-change-manager', [
            'requests' => $requests,
        ])->layout('layouts.app');
    }
}
