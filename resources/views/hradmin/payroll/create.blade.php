<x-app-layout title="New Payroll Period">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">New Payroll Period</h2>
    </x-slot>

    <form method="POST" action="{{ route('hr.payroll.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4 max-w-xl">
        @csrf
        <div>
            <x-input-label for="period_name" value="Period Name" />
            <x-text-input id="period_name" name="period_name" type="text" class="mt-1 block w-full" value="{{ old('period_name', now()->format('F Y')) }}" required />
            <x-input-error :messages="$errors->get('period_name')" class="mt-1" />
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="start_date" value="Start Date" />
                <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" value="{{ old('start_date', now()->startOfMonth()->format('Y-m-d')) }}" required />
                <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="end_date" value="End Date" />
                <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" value="{{ old('end_date', now()->endOfMonth()->format('Y-m-d')) }}" required />
                <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
            </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('hr.payroll.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Create Period</button>
        </div>
    </form>
</x-app-layout>
