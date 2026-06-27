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

<div>
    <x-input-label for="currency" value="Currency Code" />
    <x-text-input id="currency" name="currency" class="mt-1 block w-full" value="{{ old('currency', $client?->currency ?? 'AED') }}" maxlength="10" />
    <x-input-error :messages="$errors->get('currency')" class="mt-1" />
</div>

<div>
    <x-input-label value="Starter Prompts (one per line)" />
    <textarea name="starter_prompts_raw" rows="3"
              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('starter_prompts_raw', implode("\n", $client?->starter_prompts ?? [])) }}</textarea>
    <p class="mt-1 text-xs text-gray-400">These appear as clickable suggestions in the empty chat state.</p>
</div>

<div class="space-y-4 rounded-2xl border border-slate-200/70 p-4 dark:border-white/10"
     x-data="{
        brand: '{{ old('brand_color', $client?->brand_color ?? '#4f46e5') }}',
        accent: '{{ old('accent_color', $client?->accent_color ?? '#06b6d4') }}',
        mode: '{{ old('theme_mode', $client?->theme_mode ?? 'light') }}'
     }">
    <x-ui.section-heading>Appearance</x-ui.section-heading>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="brand_color" value="Brand color" />
            <div class="mt-1 flex items-center gap-2">
                <input type="color" x-model="brand" class="h-10 w-12 rounded-lg border-0 bg-transparent p-0">
                <x-text-input id="brand_color" name="brand_color" x-model="brand" class="flex-1" placeholder="#4f46e5" />
            </div>
            <x-input-error :messages="$errors->get('brand_color')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="accent_color" value="Accent color" />
            <div class="mt-1 flex items-center gap-2">
                <input type="color" x-model="accent" class="h-10 w-12 rounded-lg border-0 bg-transparent p-0">
                <x-text-input id="accent_color" name="accent_color" x-model="accent" class="flex-1" placeholder="#06b6d4" />
            </div>
            <x-input-error :messages="$errors->get('accent_color')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="theme_mode" value="Theme mode" />
            <select id="theme_mode" name="theme_mode" x-model="mode" class="glass-input mt-1">
                @foreach(['light' => 'Light', 'dark' => 'Dark', 'auto' => 'Auto (follow device)'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(old('theme_mode', $client?->theme_mode ?? 'light') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="bg_style" value="Background style" />
            <select id="bg_style" name="bg_style" class="glass-input mt-1">
                @foreach(['mesh' => 'Mesh', 'aurora' => 'Aurora (animated)', 'solid' => 'Solid', 'dots' => 'Dots'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(old('bg_style', $client?->bg_style ?? 'mesh') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Live preview --}}
    <div class="rounded-2xl border border-slate-200/70 p-4 dark:border-white/10"
         :class="mode === 'dark' ? 'bg-slate-900' : 'bg-slate-50'">
        <p class="mb-2 text-xs font-medium uppercase tracking-wide" :class="mode === 'dark' ? 'text-slate-400' : 'text-slate-500'">Preview</p>
        <div class="flex items-center gap-3 rounded-xl p-3 backdrop-blur"
             :style="`background: ${mode === 'dark' ? 'rgba(15,23,42,.6)' : 'rgba(255,255,255,.7)'}`">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl text-white" :style="`background:${brand}`">A</span>
            <div class="flex-1">
                <div class="h-2 w-24 rounded-full" :style="`background:${brand}`"></div>
                <div class="mt-1 h-2 w-16 rounded-full" :style="`background:${accent}`"></div>
            </div>
            <span class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white" :style="`background:${brand}`">Button</span>
        </div>
    </div>
</div>

<div class="flex items-center gap-2">
    <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded" {{ old('is_active', $client?->is_active ?? true) ? 'checked' : '' }}>
    <x-input-label for="is_active" value="Active" class="mb-0" />
</div>
