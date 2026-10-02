<x-app-layout title="New Announcement">
    <x-slot name="header">
        <h2 class="page-title">New Announcement</h2>
    </x-slot>

    <form method="POST" action="{{ route('announcements.store') }}" enctype="multipart/form-data" class="form-card max-w-2xl">
        @csrf
        <div>
            <x-input-label for="title" value="Title" />
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" value="{{ old('title') }}" required />
            <x-input-error :messages="$errors->get('title')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="5" class="input mt-1.5 block w-full" required>{{ old('description') }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="category" value="Category" />
                <x-text-input id="category" name="category" type="text" class="mt-1 block w-full" value="{{ old('category') }}" placeholder="e.g. Meeting, Holiday" />
                <x-input-error :messages="$errors->get('category')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="priority" value="Priority" />
                <select id="priority" name="priority" class="input mt-1.5 block w-full">
                    <option value="normal" @selected(old('priority') === 'normal')>Normal</option>
                    <option value="important" @selected(old('priority') === 'important')>Important</option>
                    <option value="urgent" @selected(old('priority') === 'urgent')>Urgent</option>
                </select>
                <x-input-error :messages="$errors->get('priority')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="department_id" value="Target Department" />
                <select id="department_id" name="department_id" class="input mt-1.5 block w-full">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
            </div>
        </div>
        <div>
            <x-input-label for="attachment" value="Attachment (optional)" />
            <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm">
            <x-input-error :messages="$errors->get('attachment')" class="mt-1" />
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('announcements.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Publish</button>
        </div>
    </form>
</x-app-layout>
