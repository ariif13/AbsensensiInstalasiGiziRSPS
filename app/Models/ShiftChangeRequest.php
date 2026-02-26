<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'current_shift_id',
        'requested_shift_id',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_note',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentShift()
    {
        return $this->belongsTo(Shift::class, 'current_shift_id');
    }

    public function requestedShift()
    {
        return $this->belongsTo(Shift::class, 'requested_shift_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
