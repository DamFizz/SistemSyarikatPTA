<x-app-layout title="Add Department">
    <x-slot name="header">
        <h2 class="page-title">Add Department</h2>
    </x-slot>

    <form method="POST" action="{{ route('hr.departments.store') }}" class="form-card max-w-2xl">
        @csrf
        <div>
            <x-input-label for="name" value="Department Name" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="3" class="input mt-1.5 block w-full">{{ old('description') }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('hr.departments.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Create Department</button>
        </div>
    </form>
</x-app-layout>
