<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Holiday extends Model
{
    protected $fillable = [
        'date',
        'name',
        'description',
        'is_recurring',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    /**
     * Check if a given date is a holiday.
     */
    public static function isHoliday($date = null): bool
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();
        
        // Check exact date match
        $exactMatch = self::where('date', $date->format('Y-m-d'))->exists();
        if ($exactMatch) return true;
        
        // Check recurring holidays (same month/day, any year)
        $recurringMatch = self::where('is_recurring', true)
            ->whereMonth('date', $date->month)
            ->whereDay('date', $date->day)
            ->exists();
            
        return $recurringMatch;
    }

    /**
     * Get holiday info for a given date.
     */
    public static function getHolidayFor($date = null): ?self
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();
        
        // Check exact date first
        $holiday = self::where('date', $date->format('Y-m-d'))->first();
        if ($holiday) return $holiday;
        
        // Check recurring
        return self::where('is_recurring', true)
            ->whereMonth('date', $date->month)
            ->whereDay('date', $date->day)
            ->first();
    }

    /**
     * Get upcoming holidays.
     */
    public static function upcoming(int $days = 30)
    {
        $today = Carbon::today();
        $endDate = $today->copy()->addDays($days);

        $exactHolidays = self::whereBetween('date', [$today, $endDate])->get();

        $monthDayPairs = collect(range(0, $days))
            ->map(fn ($offset) => $today->copy()->addDays($offset))
            ->map(fn (Carbon $date) => ['month' => $date->month, 'day' => $date->day])
            ->unique(fn (array $pair) => $pair['month'].'-'.$pair['day'])
            ->values();

        $recurringHolidays = self::where('is_recurring', true)
            ->where(function ($query) use ($monthDayPairs) {
                foreach ($monthDayPairs as $pair) {
                    $query->orWhere(function ($subQuery) use ($pair) {
                        $subQuery->whereMonth('date', $pair['month'])
                            ->whereDay('date', $pair['day']);
                    });
                }
            })
            ->get();

        return $exactHolidays
            ->concat($recurringHolidays)
            ->unique(fn ($holiday) => $holiday->is_recurring ? 'R-'.$holiday->date->format('m-d') : 'E-'.$holiday->date->format('Y-m-d'))
            ->sortBy(fn ($holiday) => $holiday->is_recurring
                ? Carbon::create($today->year, $holiday->date->month, $holiday->date->day)->format('Y-m-d')
                : $holiday->date->format('Y-m-d'))
            ->values();
    }
}
