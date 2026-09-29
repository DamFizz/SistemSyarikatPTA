<x-app-layout title="Apply Leave">
    <x-slot name="header">
        <h2 class="page-title">Apply Leave</h2>
    </x-slot>

    <form method="POST" action="{{ route('employee.leave.store') }}" enctype="multipart/form-data" class="form-card max-w-2xl">
        @csrf
        <div>
            <x-input-label for="leave_type_id" value="Leave Type" />
            <select id="leave_type_id" name="leave_type_id" class="input mt-1.5 block w-full" required>
                <option value="">Select leave type</option>
                @foreach ($leaveTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>
                        {{ $type->name }} @if($balances->has($type->id)) ({{ $balances[$type->id]->remaining_days }} days left) @endif
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('leave_type_id')" class="mt-1" />
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="start_date" value="Start Date" />
                <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" value="{{ old('start_date') }}" required />
                <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="end_date" value="End Date" />
                <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" value="{{ old('end_date') }}" required />
                <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
            </div>
        </div>
        <div>
            <x-input-label for="reason" value="Reason" />
            <textarea id="reason" name="reason" rows="3" class="input mt-1.5 block w-full" required>{{ old('reason') }}</textarea>
            <x-input-error :messages="$errors->get('reason')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="attachment" value="Attachment (e.g. Medical Certificate)" />
            <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm">
            <x-input-error :messages="$errors->get('attachment')" class="mt-1" />
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('employee.leave.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Submit Application</button>
        </div>
    </form>
</x-app-layout>
