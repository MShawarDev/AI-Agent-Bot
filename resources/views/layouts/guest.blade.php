<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <x-theme-style />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased dark:text-slate-100">
        <x-app-background />
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <a href="/" class="mb-6 flex items-center gap-2">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand text-2xl font-bold text-white shadow-glow">{{ mb_substr(config('app.name', 'A'), 0, 1) }}</span>
                <span class="text-xl font-bold tracking-tight">{{ config('app.name') }}</span>
            </a>
            <div class="w-full max-w-md">
                <div class="glass-strong animate-fade-up p-6 sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
