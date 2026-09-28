<x-app-layout title="Add Team Member">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Add Team Member</h2>
        <p class="text-sm text-slate-500 mt-1">Added to your department, reporting directly to you. New accounts start as "Employee" role on probation.</p>
    </x-slot>

    <form method="POST" action="{{ route('manager.employees.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 space-y-6 max-w-3xl">
        @csrf

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Account</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="name" value="Full Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email') }}" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password" value="Password" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirm Password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Profile</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="employee_code" value="Employee Code" />
                    <x-text-input id="employee_code" name="employee_code" type="text" class="mt-1 block w-full" value="{{ old('employee_code') }}" required />
                    <x-input-error :messages="$errors->get('employee_code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="ic_number" value="IC / Identification Number" />
                    <x-text-input id="ic_number" name="ic_number" type="text" class="mt-1 block w-full" value="{{ old('ic_number') }}" required />
                    <x-input-error :messages="$errors->get('ic_number')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" value="{{ old('phone') }}" />
                </div>
                <div>
                    <x-input-label for="gender" value="Gender" />
                    <select id="gender" name="gender" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">-</option>
                        <option value="male" @selected(old('gender') === 'male')>Male</option>
                        <option value="female" @selected(old('gender') === 'female')>Female</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="dob" value="Date of Birth" />
                    <x-text-input id="dob" name="dob" type="date" class="mt-1 block w-full" value="{{ old('dob') }}" />
                </div>
                <div>
                    <x-input-label for="join_date" value="Join Date" />
                    <x-text-input id="join_date" name="join_date" type="date" class="mt-1 block w-full" value="{{ old('join_date', date('Y-m-d')) }}" required />
                    <x-input-error :messages="$errors->get('join_date')" class="mt-1" />
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <x-input-label for="address" value="Address" />
                    <textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">{{ old('address') }}</textarea>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Employment Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="office_id" value="Office Location" />
                    <select id="office_id" name="office_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" required>
                        <option value="">Select office</option>
                        @foreach ($offices as $office)
                            <option value="{{ $office->id }}" @selected(old('office_id') == $office->id)>{{ $office->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('office_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="position" value="Position / Job Title" />
                    <x-text-input id="position" name="position" type="text" class="mt-1 block w-full" value="{{ old('position') }}" required />
                    <x-input-error :messages="$errors->get('position')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="employment_type" value="Employment Type" />
                    <select id="employment_type" name="employment_type" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach (['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'intern' => 'Intern'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employment_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('employment_type')" class="mt-1" />
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('manager.employees.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Add Team Member</button>
        </div>
    </form>
</x-app-layout>
