@props(['action', 'label' => null, 'message' => null, 'method' => 'DELETE', 'icon' => 'trash-2'])

<form method="POST" action="{{ $action }}" class="inline"
      onsubmit="return confirm(@js($message ?? __('Are you sure? This cannot be undone.')))">
    @csrf
    @method($method)
    <button type="submit" {{ $attributes->merge(['class' => 'btn btn-sm btn-danger-soft']) }} title="{{ $label ?? __('Delete') }}">
        <x-dynamic-component :component="'lucide-'.$icon" class="size-4" />
        @if ($label)<span>{{ $label }}</span>@endif
    </button>
</form>
