<x-guest-layout>
    <div class="mb-7">
        <h2 class="text-2xl font-bold">Welcome back</h2>
        <p class="muted mt-1.5">Sign in to continue to your workspace.</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ show: false, submitting: false }" @submit="submitting = true">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" class="mt-1.5 block w-full py-2.5" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@company.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold text-emerald-700 hover:text-emerald-900" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>
            <div class="relative mt-1.5">
                <x-text-input id="password" class="block w-full py-2.5 pe-11" ::type="show ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                <button type="button" @click="show = !show" class="absolute inset-y-0 end-0 flex items-center px-3.5 text-slate-400 hover:text-slate-600" :aria-label="show ? 'Hide password' : 'Show password'">
                    <x-icon name="eye" class="h-[18px] w-[18px]" />
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2.5">
            <input id="remember_me" type="checkbox" name="remember">
            <span class="text-sm text-slate-600">{{ __('Keep me signed in') }}</span>
        </label>

        <button type="submit" class="btn-primary w-full py-3" :disabled="submitting">
            <span x-show="!submitting">{{ __('Sign in') }}</span>
            <span x-show="submitting" x-cloak>Signing in…</span>
            <x-icon name="arrow-right" class="h-4 w-4" x-show="!submitting" />
        </button>
    </form>

    <div class="mt-7 flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
        <x-icon name="lock" class="h-4 w-4 shrink-0 text-slate-400" />
        Protected by role-based access &amp; full audit logging.
    </div>
</x-guest-layout>
