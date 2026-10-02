<x-app-layout title="My Profile">
    <x-slot name="header">
        <p class="eyebrow">Account</p>
        <h2 class="page-title mt-1">My Profile</h2>
        <p class="muted mt-1">Manage your personal details, password and account security.</p>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="surface-dark p-6 lg:row-span-4 lg:self-start">
            <div class="relative">
                @include('profile.partials.update-photo-form')
                <div class="mt-4 text-lg font-semibold text-white">{{ $user->name }}</div>
                <div class="text-sm text-slate-400">{{ $user->email }}</div>
                <div class="mt-4 inline-flex chip bg-emerald-400/10 text-emerald-300 ring-1 ring-emerald-400/20 capitalize">{{ str_replace('_', ' ', $user->role) }}</div>

                @if ($user->employee)
                    <dl class="mt-6 space-y-3 border-t border-white/10 pt-5 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Employee ID</dt><dd class="font-mono text-slate-200">{{ $user->employee->employee_code }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Position</dt><dd class="text-right text-slate-200">{{ $user->employee->position }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Department</dt><dd class="text-right text-slate-200">{{ $user->employee->department?->name }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Office</dt><dd class="text-right text-slate-200">{{ $user->employee->office?->name }}</dd></div>
                    </dl>
                @endif
            </div>
        </div>

        <div class="card p-6 sm:p-8 lg:col-span-2">
            <div class="max-w-xl">
                @include('profile.partials.install-app')
            </div>
        </div>

        <div class="card p-6 sm:p-8 lg:col-span-2">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card p-6 sm:p-8 lg:col-span-2">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card border-rose-100 p-6 sm:p-8 lg:col-span-2">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
