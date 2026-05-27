/** @type {import('tailwindcss').Config} */
import daisyui from 'daisyui';

export default {
    content: [
        './pages/**/*.{js,html,php}',
        './admin/**/*.{js,html,php}',
        './src/**/*.{js,css,vue}',
        './*.{php,html,js}',
    ],
    theme: {
        extend: {},
    },
    plugins: [daisyui],
    daisyui: {
        // On laisse les thèmes par défaut. Les pages utilisent `data-theme="light"`
        // et `data-theme="cupcake"` — les deux font partie des thèmes intégrés.
        themes: ['light', 'cupcake'],
        logs: false,
    },
};
