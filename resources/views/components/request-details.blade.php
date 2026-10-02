{{-- Reason + attachment of a leave / overtime request, shown under the employee's name. --}}
@props(['request', 'type'])

@if ($request->reason)
    <p class="mt-0.5 line-clamp-2 max-w-xs whitespace-normal text-xs font-normal text-slate-500" title="{{ $request->reason }}">{{ $request->reason }}</p>
@endif
@if ($request->attachment)
    <a href="{{ route('attachments.show', [$type, $request->id]) }}" target="_blank" rel="noopener"
       class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-sky-700 hover:underline">
        <x-icon name="document" class="h-3.5 w-3.5" /> View attachment
    </a>
@endif
