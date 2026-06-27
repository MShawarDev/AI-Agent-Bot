import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: 'rgb(var(--brand-rgb) / <alpha-value>)',
                accent: 'rgb(var(--accent-rgb) / <alpha-value>)',
            },
            borderRadius: {
                '2xl': '1.125rem',
                '3xl': '1.5rem',
            },
            boxShadow: {
                glass: '0 10px 30px -12px rgb(2 6 23 / 0.25)',
                'glow': '0 0 0 1px rgb(var(--brand-rgb) / 0.25), 0 8px 30px -8px rgb(var(--brand-rgb) / 0.45)',
            },
            keyframes: {
                aurora: {
                    '0%,100%': { transform: 'translate3d(0,0,0) scale(1)' },
                    '50%': { transform: 'translate3d(4%, -4%, 0) scale(1.15)' },
                },
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                aurora: 'aurora 18s ease-in-out infinite',
                'fade-up': 'fade-up 0.5s ease-out both',
            },
        },
    },
    plugins: [forms],
};
