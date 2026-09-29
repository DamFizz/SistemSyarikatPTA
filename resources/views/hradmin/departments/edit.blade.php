<x-app-layout title="Edit Department">
    <x-slot name="header">
        <h2 class="page-title">Edit Department</h2>
    </x-slot>

    <form method="POST" action="{{ route('hr.departments.update', $department) }}" class="form-card max-w-2xl">
        @csrf
        @method('PUT')
        <div>
            <x-input-label for="name" value="Department Name" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $department->name) }}" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="3" class="input mt-1.5 block w-full">{{ old('description', $department->description) }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="manager_id" value="Department Manager" />
            <select id="manager_id" name="manager_id" class="input mt-1.5 block w-full">
                <option value="">None</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('manager_id', $department->manager_id) == $employee->id)>{{ $employee->full_name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-400 mt-1">Only employees already in this department can be set as its manager.</p>
            <x-input-error :messages="$errors->get('manager_id')" class="mt-1" />
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('hr.departments.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save Changes</button>
        </div>
    </form>
</x-app-layout>
