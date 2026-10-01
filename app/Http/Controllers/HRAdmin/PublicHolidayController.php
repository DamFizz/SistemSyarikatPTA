<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PublicHoliday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicHolidayController extends Controller
{
    public function index(): View
    {
        $holidays = PublicHoliday::orderBy('date')->get()
            ->sortBy(fn (PublicHoliday $holiday) => $holiday->nextOccurrence()->timestamp)
            ->values();

        return view('hradmin.public-holidays.index', [
            'upcoming' => $holidays->filter(fn ($h) => $h->nextOccurrence()->gte(today())),
            'past' => $holidays->filter(fn ($h) => $h->nextOccurrence()->lt(today()))->sortByDesc('date'),
            'today' => PublicHoliday::forDate(today()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date'],
            'is_recurring' => ['nullable', 'boolean'],
        ]);
        $data['is_recurring'] = $request->boolean('is_recurring');

        $holiday = PublicHoliday::create($data);

        AuditLog::record('create', 'public_holiday', "Added public holiday \"{$holiday->name}\" ({$holiday->date->format('d M Y')})");

        return back()->with('success', "{$holiday->name} added.");
    }

    public function destroy(PublicHoliday $publicHoliday): RedirectResponse
    {
        $publicHoliday->delete();

        AuditLog::record('delete', 'public_holiday', "Removed public holiday \"{$publicHoliday->name}\"");

        return back()->with('success', "{$publicHoliday->name} removed.");
    }
}
