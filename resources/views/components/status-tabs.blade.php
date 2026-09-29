@props(['options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'], 'default' => 'pending'])

@php $current = request('status', $default); @endphp

<nav {{ $attributes->merge(['class' => 'mb-5 flex w-full gap-1 overflow-x-auto rounded-2xl border border-slate-200/80 bg-white p-1 shadow-soft sm:w-auto sm:inline-flex']) }}>
    @foreach ($options as $value => $label)
        <a href="{{ request()->fullUrlWithQuery(['status' => $value, 'page' => null]) }}"
           @class([
               'shrink-0 rounded-xl px-4 py-2 text-sm font-medium transition',
               'bg-ink-900 text-white shadow-sm' => $current === $value,
               'text-slate-500 hover:bg-slate-100 hover:text-slate-900' => $current !== $value,
           ])>{{ $label }}</a>
    @endforeach
</nav>
