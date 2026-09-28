<x-app-layout title="Submit Ticket">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Submit Helpdesk Ticket</h2>
    </x-slot>

    <form method="POST" action="{{ route('employee.tickets.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4 max-w-xl">
        @csrf
        <div>
            <x-input-label for="category_id" value="Category" />
            <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" required>
                <option value="">Select category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="title" value="Title" />
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" value="{{ old('title') }}" required />
            <x-input-error :messages="$errors->get('title')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" required>{{ old('description') }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="priority" value="Priority" />
            <select id="priority" name="priority" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
            </select>
        </div>
        <div>
            <x-input-label for="attachment" value="Attachment / Screenshot (optional)" />
            <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm">
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('employee.tickets.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Submit</button>
        </div>
    </form>
</x-app-layout>
