<?php

namespace App\Livewire\Admin;

use App\Models\Reimbursement;
use Livewire\Component;
use Livewire\WithPagination;

class ReimbursementManager extends Component
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
        $reimbursement = Reimbursement::findOrFail($id);

        // Auth Check
        $user = auth()->user();
        if (!$user->is_admin && !$user->is_superadmin) {
            if (!$user->subordinates->contains('id', $reimbursement->user_id)) {
                abort(403, 'Unauthorized action.');
            }
        }

        $reimbursement->update(['status' => 'approved']);
        
        $reimbursement->user->notify(new \App\Notifications\ReimbursementStatusUpdated($reimbursement));
        
        $this->dispatch('saved'); 
    }

    public function reject($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        
        // Auth Check
        $user = auth()->user();
        if (!$user->is_admin && !$user->is_superadmin) {
            if (!$user->subordinates->contains('id', $reimbursement->user_id)) {
                abort(403, 'Unauthorized action.');
            }
        }

        $reimbursement->update(['status' => 'rejected']);
        
        $reimbursement->user->notify(new \App\Notifications\ReimbursementStatusUpdated($reimbursement));
        
        $this->dispatch('saved');
    }

    public function render()
    {
        $user = auth()->user();
        
        $reimbursements = Reimbursement::query()
            ->with('user')
            ->when(!$user->is_admin && !$user->is_superadmin, function ($q) use ($user) {
                 return $q->whereIn('user_id', $user->subordinates->pluck('id'));
            })
            ->when($this->statusFilter, function ($query) {
                return $query->where('status', $this->statusFilter);
            })
            ->when($this->search, function ($query) {
                return $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.reimbursement-manager', [
            'reimbursements' => $reimbursements,
        ])->layout('layouts.app');
    }
}
