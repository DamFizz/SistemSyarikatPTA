{{-- Shift and salary: without them the employee gets no clock-in reminders, late status, payroll or OT pay. --}}
<div class="border-t border-slate-900/[0.06] pt-6">
    <h3 class="text-sm font-semibold text-slate-700">Work Schedule &amp; Pay</h3>
    <p class="mb-3 mt-0.5 text-xs text-slate-500">{{ $hint }}</p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <x-input-label for="shift_id" value="Work Shift" />
            <select id="shift_id" name="shift_id" class="input mt-1.5 block w-full">
                <option value="">No fixed shift</option>
                @foreach ($shifts as $shift)
                    <option value="{{ $shift->id }}" @selected((string) old('shift_id', $selectedShift) === (string) $shift->id)>
                        {{ $shift->name }} ({{ \Illuminate\Support\Carbon::parse($shift->start_time)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($shift->end_time)->format('H:i') }})
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('shift_id')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="basic_salary" value="Basic Salary (RM / month)" />
            <x-text-input id="basic_salary" name="basic_salary" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('basic_salary', $salary?->basic_salary) }}" placeholder="e.g. 3500" />
            <x-input-error :messages="$errors->get('basic_salary')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="allowance" value="Fixed Allowance (RM / month)" />
            <x-text-input id="allowance" name="allowance" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('allowance', $salary?->allowance) }}" placeholder="0" />
            <x-input-error :messages="$errors->get('allowance')" class="mt-1" />
        </div>
    </div>
</div>
