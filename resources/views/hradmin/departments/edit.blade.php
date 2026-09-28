<x-app-layout title="Edit Department">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Edit Department</h2>
    </x-slot>

    <form method="POST" action="{{ route('hr.departments.update', $department) }}" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4 max-w-xl">
        @csrf
        @method('PUT')
        <div>
            <x-input-label for="name" value="Department Name" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $department->name) }}" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">{{ old('description', $department->description) }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="manager_id" value="Department Manager" />
            <select id="manager_id" name="manager_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">None</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('manager_id', $department->manager_id) == $employee->id)>{{ $employee->full_name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-400 mt-1">Only employees already in this department can be set as its manager.</p>
            <x-input-error :messages="$errors->get('manager_id')" class="mt-1" />
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('hr.departments.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Save Changes</button>
        </div>
    </form>
</x-app-layout>
