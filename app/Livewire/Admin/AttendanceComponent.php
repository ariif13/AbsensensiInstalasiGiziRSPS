<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\JobTitle;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceComponent extends Component
{
    use AttendanceDetailTrait;
    use WithPagination, InteractsWithBanner;

    # filter
    public $startDate;
    public $endDate;
    public ?string $division = null;
    public ?string $jobTitle = null;
    public ?string $search = null;
    public array $divisionOptions = [];
    public array $jobTitleOptions = [];

    public function mount()
    {
        $this->startDate = now()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');

        $this->divisionOptions = Division::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($division) => ['id' => $division->id, 'name' => $division->name])
            ->values()
            ->all();

        $this->jobTitleOptions = JobTitle::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($jobTitle) => ['id' => $jobTitle->id, 'name' => $jobTitle->name])
            ->values()
            ->all();
    }

    public function updating($key): void
    {
        if ($key === 'search' || $key === 'division' || $key === 'jobTitle' || $key === 'startDate' || $key === 'endDate') {
            $this->resetPage();
        }
    }

    public function render()
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        
        // Validation: Prevent inverted range
        if ($start->gt($end)) {
            $temp = $start;
            $start = $end;
            $end = $temp;
        }

        $dates = $start->range($end)->toArray();

        $employees = User::where('group', 'user')
            ->when($this->search, function (Builder $q) {
                return $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('nip', 'like', '%' . $this->search . '%');
            })
            ->when($this->division, fn(Builder $q) => $q->where('division_id', $this->division))
            ->when($this->jobTitle, fn(Builder $q) => $q->where('job_title_id', $this->jobTitle))
            ->with(['division', 'jobTitle'])

            ->paginate(20);

        $employeeIds = $employees->getCollection()->pluck('id');

        $attendanceRows = Attendance::query()
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get([
                'id',
                'user_id',
                'status',
                'date',
                'latitude_in',
                'longitude_in',
                'latitude_out',
                'longitude_out',
                'attachment',
                'note',
                'time_in',
                'time_out',
                'shift_id',
                'is_suspicious',
                'suspicious_reason',
                'approval_status',
            ]);

        $shiftNames = Shift::query()
            ->whereIn('id', $attendanceRows->pluck('shift_id')->filter()->unique())
            ->pluck('name', 'id');

        $attendanceByUser = $attendanceRows
            ->map(function (Attendance $attendance) use ($shiftNames) {
                return [
                    'id' => $attendance->id,
                    'user_id' => $attendance->user_id,
                    'status' => $attendance->status,
                    'date' => optional($attendance->date)->format('Y-m-d'),
                    'latitude_in' => $attendance->latitude_in,
                    'longitude_in' => $attendance->longitude_in,
                    'latitude_out' => $attendance->latitude_out,
                    'longitude_out' => $attendance->longitude_out,
                    'coordinates' => $attendance->lat_lng,
                    'lat' => $attendance->latitude_in,
                    'lng' => $attendance->longitude_in,
                    'attachment' => $attendance->attachment ? $attendance->attachment_url : null,
                    'note' => $attendance->note,
                    'time_in' => $attendance->time_in,
                    'time_out' => $attendance->time_out,
                    'shift_id' => $attendance->shift_id,
                    'shift' => $attendance->shift_id ? ($shiftNames[$attendance->shift_id] ?? null) : null,
                    'is_suspicious' => (bool) $attendance->is_suspicious,
                    'suspicious_reason' => $attendance->suspicious_reason,
                    'approval_status' => $attendance->approval_status,
                ];
            })
            ->groupBy('user_id');

        $employees->setCollection(
            $employees->getCollection()->map(function (User $user) use ($attendanceByUser) {
                $user->setRelation('attendances', collect($attendanceByUser->get($user->id, [])));
                return $user;
            })
        );

        return view('livewire.admin.attendance', ['employees' => $employees, 'dates' => $dates]);
    }
}
