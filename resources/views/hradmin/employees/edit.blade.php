<x-app-layout title="Edit Employee">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Edit Employee</h2>
        <p class="text-sm text-slate-500 mt-1">{{ $employee->full_name }} &middot; {{ $employee->employee_code }}</p>
    </x-slot>

    <form method="POST" action="{{ route('hr.employees.update', $employee) }}" class="bg-white rounded-xl border border-slate-200 p-6 space-y-6">
        @csrf
        @method('PUT')

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Account</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="name" value="Full Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $employee->full_name) }}" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $employee->user->email) }}" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="role" value="System Role" />
                    <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" required>
                        @foreach (['employee' => 'Employee', 'manager' => 'Manager / Supervisor', 'technician' => 'Technician / IT Support', 'hr_admin' => 'HR / Admin'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $employee->user->role) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>
                <div class="flex items-end text-sm text-slate-400">
                    Password unchanged. Employee can reset it from their profile page.
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Employee Profile</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="employee_code" value="Employee Code" />
                    <x-text-input id="employee_code" name="employee_code" type="text" class="mt-1 block w-full" value="{{ old('employee_code', $employee->employee_code) }}" required />
                    <x-input-error :messages="$errors->get('employee_code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="ic_number" value="IC / Identification Number" />
                    <x-text-input id="ic_number" name="ic_number" type="text" class="mt-1 block w-full" value="{{ old('ic_number', $employee->ic_number) }}" required />
                    <x-input-error :messages="$errors->get('ic_number')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" value="{{ old('phone', $employee->phone) }}" />
                </div>
                <div>
                    <x-input-label for="gender" value="Gender" />
                    <select id="gender" name="gender" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">-</option>
                        <option value="male" @selected(old('gender', $employee->gender) === 'male')>Male</option>
                        <option value="female" @selected(old('gender', $employee->gender) === 'female')>Female</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="dob" value="Date of Birth" />
                    <x-text-input id="dob" name="dob" type="date" class="mt-1 block w-full" value="{{ old('dob', $employee->dob?->format('Y-m-d')) }}" />
                </div>
                <div>
                    <x-input-label for="join_date" value="Join Date" />
                    <x-text-input id="join_date" name="join_date" type="date" class="mt-1 block w-full" value="{{ old('join_date', $employee->join_date?->format('Y-m-d')) }}" required />
                    <x-input-error :messages="$errors->get('join_date')" class="mt-1" />
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <x-input-label for="address" value="Address" />
                    <textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">{{ old('address', $employee->address) }}</textarea>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Employment Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="department_id" value="Department" />
                    <select id="department_id" name="department_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" required>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id', $employee->department_id) == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="office_id" value="Office Location" />
                    <select id="office_id" name="office_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" required>
                        @foreach ($offices as $office)
                            <option value="{{ $office->id }}" @selected(old('office_id', $employee->office_id) == $office->id)>{{ $office->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('office_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="manager_id" value="Reporting Manager" />
                    <select id="manager_id" name="manager_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">None</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}" @selected(old('manager_id', $employee->manager_id) == $manager->id)>{{ $manager->full_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('manager_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="position" value="Position / Job Title" />
                    <x-text-input id="position" name="position" type="text" class="mt-1 block w-full" value="{{ old('position', $employee->position) }}" required />
                    <x-input-error :messages="$errors->get('position')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="employment_type" value="Employment Type" />
                    <select id="employment_type" name="employment_type" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach (['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'intern' => 'Intern'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employment_type', $employee->employment_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="employment_status" value="Employment Status" />
                    <select id="employment_status" name="employment_status" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach (['active' => 'Active', 'probation' => 'Probation', 'suspended' => 'Suspended', 'resigned' => 'Resigned', 'terminated' => 'Terminated'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employment_status', $employee->employment_status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('hr.employees.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Save Changes</button>
        </div>
    </form>
</x-app-layout>
