<x-app-layout title="Add Department">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Add Department</h2>
    </x-slot>

    <form method="POST" action="{{ route('hr.departments.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4 max-w-xl">
        @csrf
        <div>
            <x-input-label for="name" value="Department Name" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">{{ old('description') }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('hr.departments.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Create Department</button>
        </div>
    </form>
</x-app-layout>
