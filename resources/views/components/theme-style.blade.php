@php($theme = \App\Support\Theme::current())
<script>
    (function () {
        var mode = @json($theme['mode']);
        var dark = mode === 'dark' || (mode === 'auto' &&
            window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    })();
</script>
<style>
    :root {
        --brand-rgb: {{ $theme['brand_rgb'] }};
        --accent-rgb: {{ $theme['accent_rgb'] }};
        --brand: {{ $theme['brand'] }};
        --accent: {{ $theme['accent'] }};
    }
</style>
