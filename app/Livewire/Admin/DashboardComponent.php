<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class DashboardComponent extends Component
{
    use AttendanceDetailTrait;

    public $showStatModal = false;
    public $selectedStatType = '';
    public $detailList = [];

    // Pending Counts
    public $pendingLeavesCount = 0;
    public $pendingReimbursementsCount = 0;

    // Filter Properties
    public $search = '';
    public $chartFilter = 'week'; // 'week' | 'month'

    public function showStatDetail($type)
    {
        $this->selectedStatType = $type;
        $this->showStatModal = true;
        $today = date('Y-m-d');

        if ($type === 'absent') {
             // Users who have NO attendance record for today (and are users, not admins)
             $this->detailList = User::where('group', 'user')
                ->whereDoesntHave('attendances', fn($q) => $q->where('date', $today))
                ->get();
        } else {
            $query = Attendance::with(['user', 'shift'])->where('date', $today);

            if ($type === 'early_checkout') {
                 $this->detailList = $query->get()->filter(function ($attendance) {
                    if (!$attendance->time_out || !$attendance->shift) return false;
                    return $attendance->time_out->format('H:i:s') < $attendance->shift->end_time;
                });
            } else {
                // present, late, excused, sick
                $this->detailList = $query->where('status', $type)->get();
            }
        }
    }

    public function closeStatModal()
    {
        $this->showStatModal = false;
        $this->detailList = [];
    }



    public function updatedChartFilter()
    {
        $this->dispatch('chart-updated', $this->calculateChartData());
    }

    private function calculateChartData()
    {
        return Cache::remember(
            'admin-dashboard-chart-'.$this->chartFilter,
            now()->addSeconds(30),
            function () {
                $days = $this->chartFilter === 'month' ? 30 : 7;
                $startDate = now()->subDays($days - 1)->startOfDay();
                $endDate = now()->endOfDay();

                $rows = Attendance::query()
                    ->selectRaw('date, status, approval_status, COUNT(*) as total')
                    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->groupBy('date', 'status', 'approval_status')
                    ->get();

                $grouped = $rows->groupBy(fn ($row) => $row->date->format('Y-m-d'));

                $labels = [];
                $present = [];
                $late = [];
                $other = [];

                for ($i = $days - 1; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $key = $date->format('Y-m-d');
                    $daily = $grouped->get($key, collect());

                    $labels[] = $date->format('d M');
                    $present[] = (int) $daily->where('status', 'present')->sum('total');
                    $late[] = (int) $daily->where('status', 'late')->sum('total');
                    $other[] = (int) $daily
                        ->whereIn('status', ['sick', 'excused'])
                        ->where('approval_status', 'approved')
                        ->sum('total');
                }

                return [
                    'labels' => $labels,
                    'present' => $present,
                    'late' => $late,
                    'other' => $other,
                ];
            }
        );
    }

    public function render()
    {
        $reimbursementEnabled = \App\Helpers\Editions::reimbursementEnabled();

        // Fetch Pending Counts
        $user = auth()->user();
        if ($user->group === 'admin' || $user->group === 'superadmin') {
            $this->pendingLeavesCount = Attendance::where('approval_status', 'pending')->count();
            $this->pendingReimbursementsCount = $reimbursementEnabled
                ? \App\Models\Reimbursement::where('status', 'pending')->count()
                : 0;
        } else {
            // Only show requests from my subordinates
            $subordinateIds = $user->subordinates->pluck('id');
            
            $this->pendingLeavesCount = Attendance::where('approval_status', 'pending')
                ->whereIn('user_id', $subordinateIds)
                ->count();
                
            $this->pendingReimbursementsCount = $reimbursementEnabled
                ? \App\Models\Reimbursement::where('status', 'pending')
                    ->whereIn('user_id', $subordinateIds)
                    ->count()
                : 0;
        }

        /** @var Collection<Attendance>  */
        $attendances = Attendance::with('shift')->where('date', date('Y-m-d'))->get();

        /** @var Collection<User>  */
        $employees = User::where('group', 'user')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('nip', 'like', '%' . $this->search . '%');
                });
            })
            ->paginate(20)
            ->through(function (User $user) use ($attendances) {
                return $user->setAttribute(
                    'attendance',
                    $attendances
                        ->where(fn (Attendance $attendance) => $attendance->user_id === $user->id)
                        ->first(),
                );
            });

        $employeesCount = User::where('group', 'user')->count();
        $presentCount = $attendances->where(fn ($attendance) => $attendance->status === 'present')->count();
        $lateCount = $attendances->where(fn ($attendance) => $attendance->status === 'late')->count();
        
        // Filter stats to approved only for leaves
        $excusedCount = $attendances->where(fn ($attendance) => $attendance->status === 'excused' && $attendance->approval_status === 'approved')->count();
        $sickCount = $attendances->where(fn ($attendance) => $attendance->status === 'sick' && $attendance->approval_status === 'approved')->count();
        
        $absentCount = $employeesCount - ($presentCount + $lateCount + $excusedCount + $sickCount);

        // Early Checkout Calculation
        $earlyCheckoutCount = $attendances->filter(function ($attendance) {
            if (!$attendance->time_out || !$attendance->shift) return false;
            // time_out is Carbon, shift->end_time is String 'H:i:s'
            return $attendance->time_out->format('H:i:s') < $attendance->shift->end_time;
        })->count();

        // Activity Logs (Optimized Storage - User Activities Only)
        $recentLogs = \App\Models\ActivityLog::with('user')
            ->whereHas('user', function ($query) {
                $query->where('group', 'user');
            })
            ->latest('updated_at')
            ->take(5)
            ->get();

        // Users checked in but not checked out (Overdue)
        // Includes today (if shift ended) and previous days
        $overdueUsers = Attendance::with(['user', 'shift'])
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->orderByDesc('date')
            ->take(10) // Limit to prevent overflow
            ->get()
            ->filter(function ($attendance) {
                if (!$attendance->shift) return false;
                
                // If date is before today, it's definitely overdue
                if ($attendance->date < now()->format('Y-m-d')) {
                    return true;
                }
                
                // If date is today, check if current time > shift end time
                if ($attendance->date === now()->format('Y-m-d')) {
                    return now()->format('H:i:s') > $attendance->shift->end_time;
                }
                
                return false;
            });

        // Calendar Data: Leaves in current month (Grouped)
        $rawLeaves = Attendance::with('user')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->whereIn('status', ['sick', 'excused'])
            ->where('approval_status', 'approved') // Only approved
            ->orderBy('user_id')
            ->orderBy('date')
            ->get();

        $calendarLeaves = collect();
        if ($rawLeaves->isNotEmpty()) {
            $grouped = $rawLeaves->groupBy(function ($item) {
                return $item->user_id . '-' . $item->status;
            });

            foreach ($grouped as $group) {
                // Determine consecutive dates
                $tempGroup = [];
                foreach ($group as $leave) {
                    if (empty($tempGroup)) {
                        $tempGroup[] = $leave;
                        continue;
                    }

                    $last = end($tempGroup);
                    // Check if consecutive (1 day difference)
                    if ($last->date->diffInDays($leave->date) == 1) {
                        $tempGroup[] = $leave;
                    } else {
                        // Push previous group
                        $calendarLeaves->push($this->formatLeaveGroup($tempGroup));
                        $tempGroup = [$leave];
                    }
                }
                // Push last group
                if (!empty($tempGroup)) {
                    $calendarLeaves->push($this->formatLeaveGroup($tempGroup));
                }
            }
        }

        return view('livewire.admin.dashboard', [
            'employees' => $employees,
            'employeesCount' => $employeesCount,
            'presentCount' => $presentCount,
            'lateCount' => $lateCount,
            'earlyCheckoutCount' => $earlyCheckoutCount,
            'excusedCount' => $excusedCount,
            'sickCount' => $sickCount,
            'absentCount' => $absentCount,
            'recentLogs' => $recentLogs,
            'chartData' => $this->calculateChartData(),
            'overdueUsers' => $overdueUsers,
            'calendarLeaves' => $calendarLeaves,
        ]);
    }

    public function notifyUser($attendanceId)
    {
        $attendance = Attendance::find($attendanceId);
        if ($attendance && $attendance->user && $attendance->user->email) {
            \Illuminate\Support\Facades\Mail::to($attendance->user->email)->send(new \App\Mail\CheckoutReminderMail($attendance->user));
            
            // Log it
            \App\Models\ActivityLog::record('Notification Sent', 'Sent checkout reminder to ' . $attendance->user->name);
        }
    }

    private function formatLeaveGroup($leaves)
    {
        $first = $leaves[0];
        $last = end($leaves);
        $count = count($leaves);

        $dateDisplay = $first->date->format('d M');
        if ($count > 1) {
            if ($first->date->format('M') == $last->date->format('M')) {
                $dateDisplay .= ' - ' . $last->date->format('d M Y');
            } else {
                $dateDisplay .= ' - ' . $last->date->format('d M Y');
            }
            $dateDisplay .= ' (' . $count . ' days)';
        } else {
            $dateDisplay = $first->date->format('d M Y');
        }

        return [
            'title' => $first->user->name,
            'date_display' => $dateDisplay,
            'start_date' => $first->date, // Raw date for parsing
            'status' => $first->status
        ];
    }
}
