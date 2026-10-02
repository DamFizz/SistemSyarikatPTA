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
                    '100%': { opacity: '1', transform: 'none' },
                },
                ripple: {
                    '0%': { transform: 'scale(0.6)', opacity: '0.6' },
                    '100%': { transform: 'scale(1.9)', opacity: '0' },
                },
                'scan-line': {
                    '0%, 100%': { top: '8%' },
                    '50%': { top: '88%' },
                },
                // Entrance used by the staggered reveal (ends on transform:none so fixed modals stay unconfined).
                rise: {
                    '0%': { opacity: '0', transform: 'translateY(14px) scale(0.985)', filter: 'blur(4px)' },
                    '100%': { opacity: '1', transform: 'none', filter: 'none' },
                },
                'pop-in': {
                    '0%': { opacity: '0', transform: 'scale(0.6)' },
                    '60%': { opacity: '1', transform: 'scale(1.08)' },
                    '100%': { transform: 'scale(1)' },
                },
                sheen: {
                    '0%': { transform: 'translateX(-120%) skewX(-18deg)' },
                    '100%': { transform: 'translateX(220%) skewX(-18deg)' },
                },
                drift: {
                    '0%, 100%': { transform: 'translate3d(0, 0, 0) scale(1)' },
                    '33%': { transform: 'translate3d(6vw, 4vh, 0) scale(1.08)' },
                    '66%': { transform: 'translate3d(-4vw, 7vh, 0) scale(0.95)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-4px)' },
                },
            },
            animation: {
                'fade-up': 'fade-up .35s ease-out backwards',
                ripple: 'ripple 2.2s cubic-bezier(0, 0, .2, 1) infinite',
                'scan-line': 'scan-line 2.4s ease-in-out infinite',
                rise: 'rise .6s cubic-bezier(.2, .8, .2, 1) backwards',
                'pop-in': 'pop-in .45s cubic-bezier(.2, .8, .2, 1.2) backwards',
                sheen: 'sheen .9s ease-out',
                drift: 'drift 26s ease-in-out infinite',
                float: 'float 3.2s ease-in-out infinite',
            },
            transitionTimingFunction: {
                spring: 'cubic-bezier(.2, .8, .2, 1.15)',
            },
        },
    },

    plugins: [forms],
};
