import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Space Grotesk', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                gacov: {
                    primary:        '#00D4FF',
                    'primary-dark': '#0099BB',
                    'primary-light':'#66E8FF',
                    secondary:      '#7C3AED',
                    accent:         '#F59E0B',
                    success:        '#10B981',
                    warning:        '#F59E0B',
                    error:          '#EF4444',
                    info:           '#3B82F6',
                    'bg-base':      '#0A0E1A',
                    'bg-surface':   '#111827',
                    'bg-elevated':  '#1F2937',
                    'bg-overlay':   '#374151',
                },
            },
        },
    },

    plugins: [forms],
};
