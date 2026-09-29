import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                ink: {
                    950: '#060a13',
                    900: '#0a1020',
                    800: '#111a2e',
                    700: '#1b2640',
                    600: '#2a3756',
                },
                canvas: '#f4f6fa',
            },
            boxShadow: {
                soft: '0 1px 2px rgba(16, 24, 40, 0.04), 0 4px 16px -4px rgba(16, 24, 40, 0.06)',
                lift: '0 2px 4px rgba(16, 24, 40, 0.04), 0 16px 32px -12px rgba(16, 24, 40, 0.14)',
                glow: '0 0 0 1px rgba(16, 185, 129, 0.25), 0 8px 24px -6px rgba(16, 185, 129, 0.45)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(6px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                ripple: {
                    '0%': { transform: 'scale(0.6)', opacity: '0.6' },
                    '100%': { transform: 'scale(1.9)', opacity: '0' },
                },
                'scan-line': {
                    '0%, 100%': { top: '8%' },
                    '50%': { top: '88%' },
                },
            },
            animation: {
                'fade-up': 'fade-up .35s ease-out both',
                ripple: 'ripple 2.2s cubic-bezier(0, 0, .2, 1) infinite',
                'scan-line': 'scan-line 2.4s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
