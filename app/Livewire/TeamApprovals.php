<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TeamApprovals extends Component
{
    use WithPagination;

    public $activeTab = 'leaves'; // leaves, reimbursements, overtimes
    public $search = '';

    public function mount()
    {
        if (!\App\Helpers\Editions::reimbursementEnabled() && $this->activeTab === 'reimbursements') {
            $this->activeTab = 'leaves';
        }

        if (!\App\Helpers\Editions::overtimeEnabled() && $this->activeTab === 'overtimes') {
            $this->activeTab = 'leaves';
        }
    }

    public function switchTab($tab)
    {
        if ($tab === 'reimbursements' && !\App\Helpers\Editions::reimbursementEnabled()) {
            $this->activeTab = 'leaves';
            return;
        }

        if ($tab === 'overtimes' && !\App\Helpers\Editions::overtimeEnabled()) {
            $this->activeTab = 'leaves';
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ... mount and switchTab

    public function approveOvertime($id)
    {
        if (!\App\Helpers\Editions::overtimeEnabled()) {
            return;
        }

        $overtime = Overtime::find($id);

        if (!$overtime) {
            return;
        }

        if (!$this->isSubordinate($overtime->user_id)) {
            return;
        }

        $overtime->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        $this->dispatch('refresh');
        session()->flash('success', 'Overtime request approved.');
    }

    public function rejectOvertime($id)
    {
        if (!\App\Helpers\Editions::overtimeEnabled()) {
            return;
        }

        $overtime = Overtime::find($id);

        if (!$overtime) {
            return;
        }

        if (!$this->isSubordinate($overtime->user_id)) {
            return;
        }

        $overtime->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
        ]);

        $this->dispatch('refresh');
        session()->flash('success', 'Overtime request rejected.');
    }
    
    // ... existing approve/reject methods for leave/reimbursement

    public function render()
    {
        $user = Auth::user();
        $subordinateIds = $user->subordinates->pluck('id');

        $leaves = collect();
        $reimbursements = collect();
        $overtimes = collect();

        if ($this->activeTab === 'leaves') {
            $leaves = Attendance::whereIn('user_id', $subordinateIds)
                ->where('approval_status', 'pending')
                ->where('status', '!=', 'present') // Only leaves
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } elseif ($this->activeTab === 'reimbursements' && \App\Helpers\Editions::reimbursementEnabled()) {
            $reimbursements = Reimbursement::whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } elseif (\App\Helpers\Editions::overtimeEnabled()) {
            $overtimes = Overtime::whereIn('user_id', $subordinateIds)
               ->where('status', 'pending')
               ->orderBy('created_at', 'desc')
               ->paginate(10);
        }

        return view('livewire.team-approvals', [
            'leaves' => $leaves,
            'reimbursements' => $reimbursements,
            'overtimes' => $overtimes,
        ])->layout('layouts.app');
    }
}
