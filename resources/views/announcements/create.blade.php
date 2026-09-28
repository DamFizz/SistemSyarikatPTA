<x-app-layout title="New Announcement">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">New Announcement</h2>
    </x-slot>

    <form method="POST" action="{{ route('announcements.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4 max-w-2xl">
        @csrf
        <div>
            <x-input-label for="title" value="Title" />
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" value="{{ old('title') }}" required />
            <x-input-error :messages="$errors->get('title')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" required>{{ old('description') }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="category" value="Category" />
                <x-text-input id="category" name="category" type="text" class="mt-1 block w-full" value="{{ old('category') }}" placeholder="e.g. Meeting, Holiday" />
            </div>
            <div>
                <x-input-label for="priority" value="Priority" />
                <select id="priority" name="priority" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="normal" @selected(old('priority') === 'normal')>Normal</option>
                    <option value="important" @selected(old('priority') === 'important')>Important</option>
                    <option value="urgent" @selected(old('priority') === 'urgent')>Urgent</option>
                </select>
            </div>
            <div>
                <x-input-label for="department_id" value="Target Department" />
                <select id="department_id" name="department_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <x-input-label for="attachment" value="Attachment (optional)" />
            <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm">
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('announcements.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Publish</button>
        </div>
    </form>
</x-app-layout>
