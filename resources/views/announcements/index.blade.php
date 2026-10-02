@php
    $user = Auth::user();
    $deletableIds = $announcements->getCollection()->filter(fn ($a) => $a->canBeDeletedBy($user))->pluck('id')->values();
    $canManage = $deletableIds->isNotEmpty();
@endphp

<x-app-layout title="Announcements">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow">Company news</p>
                <h2 class="page-title mt-1">Announcements</h2>
                <p class="muted mt-1">{{ $announcements->total() }} announcement(s) visible to you.</p>
            </div>
            @if ($user->hasRole('super_admin', 'hr_admin', 'manager'))
                <a href="{{ route('announcements.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New announcement</a>
            @endif
        </div>
    </x-slot>

    <div x-data="announcementManager(@js($deletableIds))">
        @if ($canManage)
            {{-- Bulk action bar --}}
            <div class="sticky top-[5.25rem] z-10 mb-4 flex flex-wrap items-center gap-2 rounded-full border px-4 py-2 shadow-[var(--glass-rim),var(--glass-shadow)] transition sm:px-5"
                 :class="selected.length ? 'border-rose-200/70 bg-rose-50/80 backdrop-blur-2xl' : 'border-white/60 bg-white/55 backdrop-blur-2xl'">
                <label class="flex cursor-pointer items-center gap-2.5 text-sm font-medium text-slate-700">
                    <input type="checkbox" :checked="allSelected" :indeterminate="selected.length > 0 && !allSelected" @change="toggleAll()">
                    <span x-text="selected.length ? selected.length + ' selected' : 'Select all on this page'"></span>
                </label>
                <div class="ms-auto flex items-center gap-2">
                    <button type="button" x-show="selected.length" x-cloak @click="selected = []" class="btn-ghost btn-sm">Clear</button>
                    <button type="button" @click="confirmBulk()" :disabled="!selected.length" class="btn-danger btn-sm">
                        <x-icon name="x-circle" class="h-4 w-4" /> Delete selected
                    </button>
                </div>
            </div>
        @endif

        <div class="space-y-4">
            @forelse ($announcements as $announcement)
                @php $deletable = $announcement->canBeDeletedBy($user); @endphp
                <article class="card p-5 transition sm:p-6"
                         @if ($deletable) :class="selected.includes({{ $announcement->id }}) ? 'ring-2 ring-rose-300 border-rose-200' : ''" @endif>
                    <div class="flex items-start gap-3 sm:gap-4">
                        @if ($deletable)
                            <input type="checkbox" value="{{ $announcement->id }}" x-model.number="selected" class="mt-1.5 shrink-0" aria-label="Select {{ $announcement->title }}">
                        @endif

                        <span @class([
                            'hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl sm:flex',
                            'bg-rose-50 text-rose-600' => $announcement->priority === 'urgent',
                            'bg-amber-50 text-amber-600' => $announcement->priority === 'important',
                            'bg-slate-100 text-slate-500' => ! in_array($announcement->priority, ['urgent', 'important']),
                        ])><x-icon name="megaphone" class="h-5 w-5" /></span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base font-semibold">{{ $announcement->title }}</h3>
                                        <x-status-badge :status="$announcement->priority" />
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $announcement->creator?->name ?? 'Unknown' }} &middot; {{ $announcement->created_at->format('d M Y, H:i') }}
                                        &middot; {{ $announcement->department ? $announcement->department->name.' only' : 'Company-wide' }}
                                    </p>
                                </div>
                                @if ($deletable)
                                    <button type="button" @click="confirmSingle({{ $announcement->id }}, @js($announcement->title))"
                                            class="btn-ghost btn-sm shrink-0 !text-slate-400 hover:!bg-rose-50 hover:!text-rose-600" title="Delete announcement">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                        <span class="hidden sm:inline">Delete</span>
                                    </button>
                                @endif
                            </div>
                            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $announcement->description }}</p>
                            @if ($announcement->attachment)
                                <a href="{{ route('attachments.show', ['announcement', $announcement->id]) }}" target="_blank" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium">
                                    <x-icon name="document" class="h-4 w-4" /> View attachment
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="card flex flex-col items-center gap-2 p-12 text-center text-slate-400">
                    <x-icon name="megaphone" class="h-8 w-8" />
                    No announcements yet.
                </div>
            @endforelse
        </div>

        <div class="mt-5">{{ $announcements->links() }}</div>

        @if ($canManage)
            {{-- Hidden forms --}}
            <form x-ref="singleForm" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
            <form x-ref="bulkForm" method="POST" action="{{ route('announcements.bulk-destroy') }}" class="hidden">
                @csrf
                @method('DELETE')
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
            </form>

            {{-- Confirmation modal --}}
            <div x-show="confirm.open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center" @keydown.escape.window="confirm.open = false">
                <div x-show="confirm.open" x-transition.opacity class="absolute inset-0 bg-slate-900/25 backdrop-blur-md" @click="confirm.open = false"></div>
                <div x-show="confirm.open" x-transition class="glass relative w-full max-w-md rounded-[1.75rem] !bg-white/80 p-6">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                        <x-icon name="warning" class="h-6 w-6" />
                    </div>
                    <h3 class="mt-4 text-lg font-semibold" x-text="confirm.title"></h3>
                    <p class="mt-1.5 text-sm text-slate-500" x-text="confirm.body"></p>
                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" @click="confirm.open = false" class="btn-secondary">Cancel</button>
                        <button type="button" @click="submit()" :disabled="submitting" class="btn-danger">
                            <span x-text="submitting ? 'Deleting…' : 'Delete'"></span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        function announcementManager(deletableIds) {
            return {
                selected: [],
                submitting: false,
                confirm: { open: false, mode: null, id: null, title: '', body: '' },

                get allSelected() {
                    return deletableIds.length > 0 && deletableIds.every((id) => this.selected.includes(id));
                },

                toggleAll() {
                    this.selected = this.allSelected ? [] : [...deletableIds];
                },

                confirmSingle(id, title) {
                    this.confirm = { open: true, mode: 'single', id, title: 'Delete this announcement?', body: `"${title}" will be permanently removed for everyone.` };
                },

                confirmBulk() {
                    if (!this.selected.length) return;
                    const n = this.selected.length;
                    this.confirm = { open: true, mode: 'bulk', id: null, title: `Delete ${n} announcement${n > 1 ? 's' : ''}?`, body: 'The selected announcements will be permanently removed for everyone. This cannot be undone.' };
                },

                submit() {
                    this.submitting = true;
                    if (this.confirm.mode === 'single') {
                        this.$refs.singleForm.action = @js(url('announcements')) + '/' + this.confirm.id;
                        this.$refs.singleForm.submit();
                    } else {
                        this.$refs.bulkForm.submit();
                    }
                },
            };
        }
    </script>
</x-app-layout>
