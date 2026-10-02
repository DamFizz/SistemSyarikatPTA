@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'alert-error mb-5 animate-fade-up']) }} role="alert">
        <x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0" />
        <div class="min-w-0 flex-1">
            @if ($errors->count() === 1)
                {{ $errors->first() }}
            @else
                <p class="font-semibold">Please fix {{ $errors->count() }} problems before saving:</p>
                <ul class="mt-1.5 list-disc space-y-0.5 ps-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
