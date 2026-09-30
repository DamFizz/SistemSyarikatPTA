@props(['options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'], 'default' => 'pending'])

@php $current = request('status', $default); @endphp

<nav {{ $attributes->merge(['class' => 'segmented mb-5 max-w-full overflow-x-auto']) }}>
    @foreach ($options as $value => $label)
        <a href="{{ request()->fullUrlWithQuery(['status' => $value, 'page' => null]) }}"
           @class(['segmented-item', 'segmented-item-active' => $current === $value])>{{ $label }}</a>
    @endforeach
</nav>
