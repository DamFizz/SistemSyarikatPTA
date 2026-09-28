<x-app-layout title="Request Overtime">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Request Overtime</h2>
    </x-slot>

    <form method="POST" action="{{ route('employee.overtime.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4 max-w-xl">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="date" value="Date" />
                <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" value="{{ old('date') }}" required />
                <x-input-error :messages="$errors->get('date')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="start_time" value="Start Time" />
                <x-text-input id="start_time" name="start_time" type="time" class="mt-1 block w-full" value="{{ old('start_time') }}" required />
                <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="end_time" value="End Time" />
                <x-text-input id="end_time" name="end_time" type="time" class="mt-1 block w-full" value="{{ old('end_time') }}" required />
                <x-input-error :messages="$errors->get('end_time')" class="mt-1" />
            </div>
        </div>
        <div>
            <x-input-label for="reason" value="Reason" />
            <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" required>{{ old('reason') }}</textarea>
            <x-input-error :messages="$errors->get('reason')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="attachment" value="Supporting Document (optional)" />
            <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm">
            <x-input-error :messages="$errors->get('attachment')" class="mt-1" />
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('employee.overtime.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Submit Request</button>
        </div>
    </form>
</x-app-layout>
