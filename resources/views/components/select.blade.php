@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'required' => false, 'hint' => null])

@php
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $current = (string) old($errorKey, $value);
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $name }}" class="label">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <select id="{{ $name }}" name="{{ $name }}" @required($required)
        {{ $attributes->except('class')->merge(['class' => 'input'.($errors->has($errorKey) ? ' input-error' : '')]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @if ($hint && ! $errors->has($errorKey))
        <p class="hint">{{ $hint }}</p>
    @endif
    @error($errorKey)
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
