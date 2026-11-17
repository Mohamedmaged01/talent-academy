@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false, 'bag' => 'default'])

@php
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->getBag($bag)->has($errorKey);
    $current = $type === 'password' ? null : old($errorKey, $value);
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $name }}" class="label">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
    @endif

    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" @required($required)
            {{ $attributes->except('class')->merge(['rows' => 4, 'class' => 'input'.($hasError ? ' input-error' : '')]) }}>{{ $current }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $current }}" @required($required)
            {{ $attributes->except('class')->merge(['class' => 'input'.($hasError ? ' input-error' : '')]) }}>
    @endif

    @if ($hint && ! $hasError)
        <p class="hint">{{ $hint }}</p>
    @endif
    @error($errorKey, $bag)
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
