@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'alert-error mb-5']) }}>
        <x-icon name="warning" class="h-5 w-5 shrink-0" />
        <div>{{ $errors->first() }}</div>
    </div>
@endif
