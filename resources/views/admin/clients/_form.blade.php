@php $client = $client ?? null; @endphp

<div>
    <x-input-label for="name" value="Company Name" />
    <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $client?->name) }}" required />
    <x-input-error :messages="$errors->get('name')" class="mt-1" />
</div>

<div>
    <x-input-label for="slug" value="Slug (URL-safe identifier)" />
    <x-text-input id="slug" name="slug" class="mt-1 block w-full" value="{{ old('slug', $client?->slug) }}" required />
    <x-input-error :messages="$errors->get('slug')" class="mt-1" />
</div>

<div>
    <x-input-label for="bot_name" value="Bot Name" />
    <x-text-input id="bot_name" name="bot_name" class="mt-1 block w-full" value="{{ old('bot_name', $client?->bot_name ?? 'Sales Assistant') }}" />
    <x-input-error :messages="$errors->get('bot_name')" class="mt-1" />
</div>

<div>
    <x-input-label for="system_prompt" value="System Prompt (leave blank for default)" />
    <textarea id="system_prompt" name="system_prompt" rows="5"
              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm">{{ old('system_prompt', $client?->system_prompt) }}</textarea>
    <x-input-error :messages="$errors->get('system_prompt')" class="mt-1" />
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="currency" value="Currency Code" />
        <x-text-input id="currency" name="currency" class="mt-1 block w-full" value="{{ old('currency', $client?->currency ?? 'AED') }}" maxlength="10" />
        <x-input-error :messages="$errors->get('currency')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="brand_color" value="Brand Color (hex, e.g. #4f46e5)" />
        <x-text-input id="brand_color" name="brand_color" class="mt-1 block w-full" value="{{ old('brand_color', $client?->brand_color) }}" placeholder="#4f46e5" />
        <x-input-error :messages="$errors->get('brand_color')" class="mt-1" />
    </div>
</div>

<div>
    <x-input-label value="Starter Prompts (one per line)" />
    <textarea name="starter_prompts_raw" rows="3"
              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('starter_prompts_raw', implode("\n", $client?->starter_prompts ?? [])) }}</textarea>
    <p class="mt-1 text-xs text-gray-400">These appear as clickable suggestions in the empty chat state.</p>
</div>

<div class="flex items-center gap-2">
    <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded" {{ old('is_active', $client?->is_active ?? true) ? 'checked' : '' }}>
    <x-input-label for="is_active" value="Active" class="mb-0" />
</div>
