<x-app-layout title="Public Holidays">
    <x-slot name="header">
        <p class="eyebrow">Operations</p>
        <h2 class="page-title mt-1">Public Holidays</h2>
        <p class="muted mt-1">No clock-in reminders on these days. Anyone who still works is flagged as holiday work for HR.</p>
    </x-slot>

    <x-validation-alert />

    @if ($today)
        <div class="alert-success mb-5">
            <x-icon name="sparkles" class="h-5 w-5 shrink-0" />
            <span>Today is a public holiday — <strong>{{ $today->name }}</strong>.</span>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <form method="POST" action="{{ route('hr.public-holidays.store') }}" class="form-card !p-6 lg:self-start">
            @csrf
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600"><x-icon name="plus" /></span>
                <div>
                    <h3 class="card-title">Add holiday</h3>
                    <p class="text-xs text-slate-500">e.g. Hari Raya, Deepavali, state holidays.</p>
                </div>
            </div>
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1.5 block w-full" :value="old('name')" required maxlength="120" placeholder="Hari Raya Aidilfitri" />
            </div>
            <div>
                <x-input-label for="date" value="Date" />
                <x-text-input id="date" name="date" type="date" class="mt-1.5 block w-full" :value="old('date')" required />
            </div>
            <label class="glass-inset flex cursor-pointer items-start gap-3 p-4">
                <input type="checkbox" name="is_recurring" value="1" class="mt-0.5" @checked(old('is_recurring'))>
                <span>
                    <span class="block text-sm font-semibold text-slate-900">Repeats every year</span>
                    <span class="mt-0.5 block text-xs text-slate-500">For fixed dates like National Day. Leave off for lunar holidays that move each year.</span>
                </span>
            </label>
            <button type="submit" class="btn-primary w-full">Add holiday</button>
        </form>

        <div class="space-y-5 lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming</h3>
                    <span class="chip bg-slate-900/5 text-slate-600">{{ $upcoming->count() }}</span>
                </div>
                <ul class="divide-y divide-slate-900/[0.05]">
                    @forelse ($upcoming as $holiday)
                        @php $next = $holiday->nextOccurrence(); @endphp
                        <li class="flex items-center gap-4 px-5 py-3.5">
                            <div class="glass-inset flex h-12 w-12 shrink-0 flex-col items-center justify-center !rounded-xl">
                                <span class="text-[10px] font-semibold uppercase text-emerald-600">{{ $next->format('M') }}</span>
                                <span class="text-base font-bold leading-none text-slate-900">{{ $next->format('d') }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-slate-900">{{ $holiday->name }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $next->format('l, d F Y') }}
                                    · {{ $next->isToday() ? 'today' : $next->diffForHumans(['parts' => 1]) }}
                                    @if ($holiday->is_recurring) · <span class="text-emerald-600">every year</span> @endif
                                </div>
                            </div>
                            <form method="POST" action="{{ route('hr.public-holidays.destroy', $holiday) }}" onsubmit="return confirm('Remove {{ e($holiday->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-ghost btn-sm !text-slate-400 hover:!bg-rose-500/10 hover:!text-rose-600" title="Remove">
                                    <x-icon name="x" class="h-4 w-4" />
                                </button>
                            </form>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-slate-400">No upcoming holidays. Add one on the left.</li>
                    @endforelse
                </ul>
            </div>

            @if ($past->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Past (one-off)</h3></div>
                    <ul class="divide-y divide-slate-900/[0.05]">
                        @foreach ($past as $holiday)
                            <li class="flex items-center justify-between gap-4 px-5 py-3 text-sm">
                                <span class="text-slate-500"><span class="font-medium text-slate-700">{{ $holiday->name }}</span> · {{ $holiday->date->format('d M Y') }}</span>
                                <form method="POST" action="{{ route('hr.public-holidays.destroy', $holiday) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost btn-sm !text-slate-400 hover:!text-rose-600">Remove</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
