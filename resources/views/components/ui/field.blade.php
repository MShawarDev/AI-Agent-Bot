@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false])
<div class="space-y-1.5">
    @if($label)
        <x-input-label :for="$name" :value="$label" />
    @endif
    @if($slot->isNotEmpty())
        {{ $slot }}
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
               value="{{ $value }}" @required($required)
               {{ $attributes->merge(['class' => 'glass-input']) }}>
    @endif
    <x-input-error :messages="$errors->get($name)" />
</div>
