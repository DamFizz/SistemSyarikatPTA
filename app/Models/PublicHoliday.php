<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'date', 'is_recurring'])]
class PublicHoliday extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    public function scopeOnDate(Builder $query, CarbonInterface $date): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereDate('date', $date->toDateString())
            ->orWhere(fn (Builder $r) => $r->where('is_recurring', true)
                ->whereMonth('date', $date->month)
                ->whereDay('date', $date->day)));
    }

    public static function forDate(CarbonInterface $date): ?self
    {
        return static::onDate($date)->orderBy('id')->first();
    }

    /**
     * The next date this holiday falls on, from today onwards.
     */
    public function nextOccurrence(): CarbonInterface
    {
        if (! $this->is_recurring) {
            return $this->date;
        }

        $next = $this->date->copy()->year(today()->year);

        return $next->isBefore(today()) ? $next->addYear() : $next;
    }
}
