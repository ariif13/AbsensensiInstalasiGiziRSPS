<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\Attendance;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
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
    public bool $readyToLoad = false;

    public function loadDashboardData(): void
    {
        $this->readyToLoad = true;
    }

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
        if (!$this->readyToLoad) {
            return view('livewire.admin.dashboard', [
                'employees' => new LengthAwarePaginator(collect(), 0, 20, 1, ['path' => request()->url()]),
                'employeesCount' => 0,
                'presentCount' => 0,
                'lateCount' => 0,
                'earlyCheckoutCount' => 0,
                'excusedCount' => 0,
                'sickCount' => 0,
                'absentCount' => 0,
                'recentLogs' => collect(),
                'chartData' => ['labels' => [], 'present' => [], 'late' => [], 'other' => []],
                'overdueUsers' => collect(),
                'calendarLeaves' => collect(),
            ]);
        }

        $reimbursementEnabled = \App\Helpers\Editions::reimbursementEnabled();
        $today = now()->format('Y-m-d');

        // Fetch Pending Counts
        $user = auth()->user();
        if ($user->group === 'admin' || $user->group === 'superadmin') {
            $this->pendingLeavesCount = Cache::remember('admin-dashboard:pending-leaves:all', now()->addSeconds(20), function () {
                return Attendance::where('approval_status', 'pending')->count();
            });
            $this->pendingReimbursementsCount = $reimbursementEnabled
                ? Cache::remember('admin-dashboard:pending-reimbursements:all', now()->addSeconds(20), function () {
                    return \App\Models\Reimbursement::where('status', 'pending')->count();
                })
                : 0;
        } else {
            // Only show requests from my subordinates
            $subordinateIds = $user->subordinates->pluck('id');
            
            $this->pendingLeavesCount = Cache::remember('admin-dashboard:pending-leaves:'.$user->id, now()->addSeconds(20), function () use ($subordinateIds) {
                return Attendance::where('approval_status', 'pending')
                    ->whereIn('user_id', $subordinateIds)
                    ->count();
            });
                
            $this->pendingReimbursementsCount = $reimbursementEnabled
                ? Cache::remember('admin-dashboard:pending-reimbursements:'.$user->id, now()->addSeconds(20), function () use ($subordinateIds) {
                    return \App\Models\Reimbursement::where('status', 'pending')
                        ->whereIn('user_id', $subordinateIds)
                        ->count();
                })
                : 0;
        }

        $stats = Cache::remember('admin-dashboard:stats:'.$today, now()->addSeconds(30), function () use ($today) {
            $employeesCount = User::where('group', 'user')->count();

            $presentCount = Attendance::whereDate('date', $today)
                ->where('status', 'present')
                ->count();

            $lateCount = Attendance::whereDate('date', $today)
                ->where('status', 'late')
                ->count();

            $excusedCount = Attendance::whereDate('date', $today)
                ->where('status', 'excused')
                ->where('approval_status', 'approved')
                ->count();

            $sickCount = Attendance::whereDate('date', $today)
                ->where('status', 'sick')
                ->where('approval_status', 'approved')
                ->count();

            $earlyCheckoutCount = Attendance::query()
                ->join('shifts', 'attendances.shift_id', '=', 'shifts.id')
                ->whereDate('attendances.date', $today)
                ->whereNotNull('attendances.time_out')
                ->whereRaw('attendances.time_out < shifts.end_time')
                ->count();

            $absentCount = max(0, $employeesCount - ($presentCount + $lateCount + $excusedCount + $sickCount));

            return [
                'employeesCount' => $employeesCount,
                'presentCount' => $presentCount,
                'lateCount' => $lateCount,
                'excusedCount' => $excusedCount,
                'sickCount' => $sickCount,
                'earlyCheckoutCount' => $earlyCheckoutCount,
                'absentCount' => $absentCount,
            ];
        });

        $employees = User::where('group', 'user')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('nip', 'like', '%' . $this->search . '%');
                });
            })
            ->paginate(10)
            ;

        $todayAttendances = Attendance::with('shift')
            ->whereDate('date', $today)
            ->whereIn('user_id', $employees->getCollection()->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $employees->setCollection(
            $employees->getCollection()->map(function (User $employee) use ($todayAttendances) {
                return $employee->setAttribute('attendance', $todayAttendances->get($employee->id));
            })
        );

        // Activity Logs (Optimized Storage - User Activities Only)
        $recentLogs = Cache::remember('admin-dashboard:recent-logs', now()->addSeconds(30), function () {
            return ActivityLog::with('user')
            ->whereHas('user', function ($query) {
                $query->where('group', 'user');
            })
            ->latest('updated_at')
            ->take(5)
            ->get();
        });

        // Users checked in but not checked out (Overdue)
        // Includes today (if shift ended) and previous days
        $overdueUsers = Cache::remember('admin-dashboard:overdue-users:'.$today, now()->addSeconds(30), function () use ($today) {
            return Attendance::with(['user', 'shift'])
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->orderByDesc('date')
                ->take(20)
                ->get()
                ->filter(function ($attendance) use ($today) {
                    if (!$attendance->shift) {
                        return false;
                    }

                    if ($attendance->date < $today) {
                        return true;
                    }

                    if ($attendance->date === $today) {
                        return now()->format('H:i:s') > $attendance->shift->end_time;
                    }

                    return false;
                })
                ->take(10)
                ->values();
        });

        // Calendar Data: Leaves in current month (Grouped)
        $calendarLeaves = Cache::remember('admin-dashboard:calendar-leaves:'.now()->format('Y-m'), now()->addSeconds(60), function () {
            $rawLeaves = Attendance::with('user')
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->whereIn('status', ['sick', 'excused'])
                ->where('approval_status', 'approved')
                ->orderBy('user_id')
                ->orderBy('date')
                ->get();

            $calendarLeaves = collect();
            if ($rawLeaves->isNotEmpty()) {
                $grouped = $rawLeaves->groupBy(function ($item) {
                    return $item->user_id . '-' . $item->status;
                });

                foreach ($grouped as $group) {
                    $tempGroup = [];
                    foreach ($group as $leave) {
                        if (empty($tempGroup)) {
                            $tempGroup[] = $leave;
                            continue;
                        }

                        $last = end($tempGroup);
                        if ($last->date->diffInDays($leave->date) == 1) {
                            $tempGroup[] = $leave;
                        } else {
                            $calendarLeaves->push($this->formatLeaveGroup($tempGroup));
                            $tempGroup = [$leave];
                        }
                    }

                    if (!empty($tempGroup)) {
                        $calendarLeaves->push($this->formatLeaveGroup($tempGroup));
                    }
                }
            }

            return $calendarLeaves;
        });

        return view('livewire.admin.dashboard', [
            'employees' => $employees,
            'employeesCount' => $stats['employeesCount'],
            'presentCount' => $stats['presentCount'],
            'lateCount' => $stats['lateCount'],
            'earlyCheckoutCount' => $stats['earlyCheckoutCount'],
            'excusedCount' => $stats['excusedCount'],
            'sickCount' => $stats['sickCount'],
            'absentCount' => $stats['absentCount'],
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
