<x-app-layout title="Announcements">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="page-title">Announcements</h2>
            @if (Auth::user()->hasRole('super_admin', 'hr_admin', 'manager'))
                <a href="{{ route('announcements.create') }}" class="btn-primary">
                    + New Announcement
                </a>
            @endif
        </div>
    </x-slot>

    <div class="space-y-4">
        @forelse ($announcements as $announcement)
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-slate-800">{{ $announcement->title }}</h3>
                            <x-status-badge :status="$announcement->priority" />
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ $announcement->creator->name }} &middot; {{ $announcement->created_at->format('d M Y, H:i') }}
                            @if ($announcement->department) &middot; {{ $announcement->department->name }} only @else &middot; Company-wide @endif
                        </p>
                    </div>
                </div>
                <p class="text-sm text-slate-600 mt-3 whitespace-pre-line">{{ $announcement->description }}</p>
                @if ($announcement->attachment)
                    <a href="{{ \Illuminate\Support\Facades\Storage::url($announcement->attachment) }}" target="_blank" class="text-emerald-600 text-sm underline mt-2 inline-block">View Attachment</a>
                @endif
            </div>
        @empty
            <div class="card p-8 text-center text-slate-400">No announcements yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $announcements->links() }}</div>
</x-app-layout>
