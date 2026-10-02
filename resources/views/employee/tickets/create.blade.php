<x-app-layout title="Submit Ticket">
    <x-slot name="header">
        <h2 class="page-title">Submit Helpdesk Ticket</h2>
    </x-slot>

    <form method="POST" action="{{ route('employee.tickets.store') }}" enctype="multipart/form-data" class="form-card max-w-2xl">
        @csrf
        <div>
            <x-input-label for="category_id" value="Category" />
            <select id="category_id" name="category_id" class="input mt-1.5 block w-full" required>
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
            <textarea id="description" name="description" rows="4" class="input mt-1.5 block w-full" required>{{ old('description') }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="priority" value="Priority" />
            <select id="priority" name="priority" class="input mt-1.5 block w-full">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
            </select>
            <x-input-error :messages="$errors->get('priority')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="attachment" value="Attachment / Screenshot (optional)" />
            <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm">
            <x-input-error :messages="$errors->get('attachment')" class="mt-1" />
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('employee.tickets.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Submit</button>
        </div>
    </form>
</x-app-layout>
