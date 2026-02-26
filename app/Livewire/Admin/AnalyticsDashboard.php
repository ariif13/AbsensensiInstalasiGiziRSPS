<?php

namespace App\Livewire\Admin;

use Livewire\Component;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class AnalyticsDashboard extends Component
{
    public $month;
    public $year;

    public function mount()
    {
        if (\App\Helpers\Editions::reportingLocked()) {
             session()->flash('show-feature-lock', ['title' => 'Analytics Locked', 'message' => 'Advanced Analytics is an Enterprise Feature 🔒. Please Upgrade.']);
             return redirect()->route('admin.dashboard');
        }

        $this->month = date('m');
        $this->year = date('Y');
    }

    public function updated($property)
    {
        if (in_array($property, ['month', 'year'])) {
            $this->dispatch('chart-update', 
                trend: $this->attendanceTrend, 
                metrics: $this->attendanceMetrics,
                divisionStats: $this->divisionStats,
                lateBuckets: $this->lateBuckets,
                absentStats: $this->absentStats
            );
        }
    }

    public function getAttendanceMetricsProperty()
    {
        return $this->cached('metrics', function () {
            return Attendance::whereMonth('date', $this->month)
                ->whereYear('date', $this->year)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();
        }, []);
    }

    public function getAttendanceTrendProperty()
    {
        return $this->cached('trend', function () {
            $startDate = Carbon::createFromDate($this->year, $this->month, 1);
            $endDate = $startDate->copy()->endOfMonth();

            $data = DB::table('attendances')
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->select('date', 'status', DB::raw('count(*) as total'))
                ->groupBy('date', 'status')
                ->get();

            $lookup = [];
            foreach ($data as $row) {
                $d = substr((string) $row->date, 0, 10);
                $lookup[$d][$row->status] = $row->total;
            }

            $trend = [
                'labels' => [],
                'present' => [],
                'late' => [],
                'absent' => [],
            ];

            $current = $startDate->copy();
            while ($current <= $endDate) {
                $dateStr = $current->format('Y-m-d');

                $trend['labels'][] = $current->format('d M');
                $trend['present'][] = $lookup[$dateStr]['present'] ?? 0;
                $trend['late'][] = $lookup[$dateStr]['late'] ?? 0;
                $trend['absent'][] =
                    ($lookup[$dateStr]['sick'] ?? 0)
                    + ($lookup[$dateStr]['excused'] ?? 0)
                    + ($lookup[$dateStr]['absent'] ?? 0)
                    + ($lookup[$dateStr]['alpha'] ?? 0);

                $current->addDay();
            }

            return $trend;
        }, [
            'labels' => [],
            'present' => [],
            'late' => [],
            'absent' => [],
        ]);
    }

    public function getTopDiligentEmployeesProperty()
    {
        return $this->cached('top_diligent', function () {
            $avgSecondsSql = $this->avgCheckInSecondsSql();

            return User::query()
                ->join('attendances', 'users.id', '=', 'attendances.user_id')
                ->whereMonth('attendances.date', $this->month)
                ->whereYear('attendances.date', $this->year)
                ->where('attendances.status', 'present')
                ->whereNotNull('attendances.time_in')
                ->select(
                    'users.id',
                    'users.name',
                    'users.profile_photo_path',
                    'users.job_title_id',
                    DB::raw("AVG({$avgSecondsSql}) as avg_check_in")
                )
                ->groupBy('users.id', 'users.name', 'users.profile_photo_path', 'users.job_title_id')
                ->orderBy('avg_check_in', 'asc')
                ->limit(5)
                ->get()
                ->load('jobTitle');
        }, collect());
    }

    public function getTopLateEmployeesProperty()
    {
        return $this->cached('top_late', function () {
            return User::join('attendances', 'users.id', '=', 'attendances.user_id')
                ->whereMonth('attendances.date', $this->month)
                ->whereYear('attendances.date', $this->year)
                ->where('attendances.status', 'late')
                ->select('users.id', 'users.name', 'users.profile_photo_path', DB::raw('count(*) as late_count'))
                ->groupBy('users.id', 'users.name', 'users.profile_photo_path')
                ->orderByDesc('late_count')
                ->limit(5)
                ->get();
        }, collect());
    }

    public function getTopEarlyLeaversProperty()
    {
        return $this->cached('top_early_leavers', function () {
            return User::join('attendances', 'users.id', '=', 'attendances.user_id')
                ->join('shifts', 'attendances.shift_id', '=', 'shifts.id')
                ->whereMonth('attendances.date', $this->month)
                ->whereYear('attendances.date', $this->year)
                ->whereIn('attendances.status', ['present', 'late'])
                ->whereNotNull('attendances.time_out')
                ->whereRaw('attendances.time_out < shifts.end_time')
                ->select('users.id', 'users.name', 'users.profile_photo_path', DB::raw('count(*) as early_leave_count'))
                ->groupBy('users.id', 'users.name', 'users.profile_photo_path')
                ->orderByDesc('early_leave_count')
                ->limit(5)
                ->get();
        }, collect());
    }

    public function getDivisionStatsProperty()
    {
        return $this->cached('division_stats', function () {
            $attendanceCounts = Attendance::join('users', 'attendances.user_id', '=', 'users.id')
                ->whereMonth('attendances.date', $this->month)
                ->whereYear('attendances.date', $this->year)
                ->where('attendances.status', 'present')
                ->select('users.division_id', DB::raw('count(*) as present_count'))
                ->whereNotNull('users.division_id')
                ->groupBy('users.division_id')
                ->pluck('present_count', 'users.division_id');

            $divisions = \App\Models\Division::all();

            $labels = [];
            $data = [];

            foreach ($divisions as $div) {
                $labels[] = $div->name;
                $data[] = (int) ($attendanceCounts[$div->id] ?? 0);
            }

            return ['labels' => $labels, 'data' => $data];
        }, ['labels' => [], 'data' => []]);
    }

    public function getLateBucketsProperty()
    {
        return $this->cached('late_buckets', function () {
            $lates = Attendance::with('shift')
                ->whereMonth('date', $this->month)
                ->whereYear('date', $this->year)
                ->where('status', 'late')
                ->whereNotNull('time_in')
                ->whereNotNull('shift_id')
                ->get();

            $buckets = [
                '1-15m' => 0,
                '16-30m' => 0,
                '31-60m' => 0,
                '> 60m' => 0,
            ];

            foreach ($lates as $att) {
                if (!$att->shift) {
                    continue;
                }

                $shiftStart = Carbon::parse($att->date->format('Y-m-d').' '.$att->shift->start_time);

                $checkIn = is_string($att->time_in)
                    ? Carbon::parse($att->date->format('Y-m-d').' '.$att->time_in)
                    : Carbon::parse($att->time_in);

                $diffInMinutes = $shiftStart->diffInMinutes($checkIn, false);

                if ($diffInMinutes <= 0) {
                    continue;
                }

                if ($diffInMinutes <= 15) {
                    $buckets['1-15m']++;
                } elseif ($diffInMinutes <= 30) {
                    $buckets['16-30m']++;
                } elseif ($diffInMinutes <= 60) {
                    $buckets['31-60m']++;
                } else {
                    $buckets['> 60m']++;
                }
            }

            return $buckets;
        }, [
            '1-15m' => 0,
            '16-30m' => 0,
            '31-60m' => 0,
            '> 60m' => 0,
        ]);
    }

    public function getAbsentStatsProperty()
    {
        return $this->cached('absent_stats', function () {
            return Attendance::whereMonth('date', $this->month)
                ->whereYear('date', $this->year)
                ->whereIn('status', ['sick', 'excused', 'absent', 'alpha'])
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();
        }, []);
    }

    public function getSummaryStatsProperty()
    {
        return $this->cached('summary', function () {
            $totalEmployees = User::where('group', 'user')->count();
            $totalWorkDays = $this->getWorkDaysInMonth();
            $expectedTotalAttendance = $totalEmployees * $totalWorkDays;

            $presentCount = Attendance::whereMonth('date', $this->month)
                ->whereYear('date', $this->year)
                ->where('status', 'present')
                ->count();

            $lateCount = Attendance::whereMonth('date', $this->month)
                ->whereYear('date', $this->year)
                ->where('status', 'late')
                ->count();

            return [
                'total_employees' => $totalEmployees,
                'attendance_rate' => $expectedTotalAttendance > 0 ? round(($presentCount + $lateCount) / $expectedTotalAttendance * 100, 1) : 0,
                'late_rate' => ($presentCount + $lateCount) > 0 ? round($lateCount / ($presentCount + $lateCount) * 100, 1) : 0,
                'avg_daily_attendance' => $totalWorkDays > 0 ? round(($presentCount + $lateCount) / $totalWorkDays) : 0,
            ];
        }, [
            'total_employees' => 0,
            'attendance_rate' => 0,
            'late_rate' => 0,
            'avg_daily_attendance' => 0,
        ]);
    }

    private function getWorkDaysInMonth()
    {
        $start = Carbon::createFromDate($this->year, $this->month, 1);
        $end = $start->copy()->endOfMonth();
        
        // Simple calculation: Weekdays only
        // Ideally should subtract holidays
        return $start->diffInDaysFiltered(function (Carbon $date) {
            return !$date->isWeekend();
        }, $end) + 1; // inclusive
    }

    public function render()
    {
        return view('livewire.admin.analytics-dashboard', [
            'metrics' => $this->attendanceMetrics,
            'trend' => $this->attendanceTrend,
            'divisionStats' => $this->divisionStats,
            'lateBuckets' => $this->lateBuckets,
            'absentStats' => $this->absentStats,
            'topDiligent' => $this->topDiligentEmployees,
            'topLate' => $this->topLateEmployees,
            'topEarlyLeavers' => $this->topEarlyLeavers,
            'workHoursPerDay' => (int) \App\Models\Setting::getValue('attendance.work_hours_per_day', 8),
            'summary' => $this->summaryStats,
        ]);
    }

    private function cacheKey(string $suffix): string
    {
        return sprintf('analytics:%s:%s:%s', $suffix, $this->year, $this->month);
    }

    private function cached(string $suffix, callable $resolver, mixed $fallback)
    {
        try {
            return Cache::remember($this->cacheKey($suffix), now()->addSeconds(45), $resolver);
        } catch (\Throwable $exception) {
            report($exception);
            return $fallback;
        }
    }

    private function avgCheckInSecondsSql(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "(CAST(strftime('%H', attendances.time_in) AS INTEGER) * 3600 + CAST(strftime('%M', attendances.time_in) AS INTEGER) * 60 + CAST(strftime('%S', attendances.time_in) AS INTEGER))";
        }

        if ($driver === 'pgsql') {
            return "EXTRACT(EPOCH FROM attendances.time_in::time)";
        }

        return 'TIME_TO_SEC(TIME(attendances.time_in))';
    }
}
