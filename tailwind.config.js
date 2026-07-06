import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Roboto', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ve: {
                    yellow: '#FFCC00',
                    blue: '#002776',
                    red: '#CF142B',
                },
                m3: {
                    primary: '#002776',
                    'on-primary': '#ffffff',
                    'primary-container': '#d6e3ff',
                    'on-primary-container': '#001a49',
                    secondary: '#cf142b',
                    'on-secondary': '#ffffff',
                    'secondary-container': '#ffdad6',
                    'on-secondary-container': '#410002',
                    tertiary: '#9a7200',
                    'on-tertiary': '#ffffff',
                    'tertiary-container': '#ffe088',
                    'on-tertiary-container': '#2a1800',
                    surface: '#fffbf8',
                    'on-surface': '#1a1c1e',
                    'on-surface-variant': '#45464f',
                    'surface-container': '#f3f0eb',
                    'surface-container-high': '#ede9e4',
                    'surface-container-highest': '#e7e3de',
                    outline: '#767680',
                    'outline-variant': '#c6c5d0',
                    error: '#ba1a1a',
                    'on-error': '#ffffff',
                    'error-container': '#ffdad6',
                    success: '#006e1c',
                    'success-container': '#9df996',
                    warning: '#7d5800',
                    'warning-container': '#ffdea3',
                },
            },
            borderRadius: {
                xs: '4px',
                sm: '8px',
                md: '12px',
                lg: '16px',
                xl: '28px',
            },
            boxShadow: {
                'm3-1': '0 1px 2px rgba(0, 39, 118, 0.08), 0 1px 3px rgba(0, 39, 118, 0.12)',
                'm3-2': '0 2px 6px rgba(0, 39, 118, 0.1), 0 4px 12px rgba(0, 39, 118, 0.08)',
                'm3-3': '0 4px 8px rgba(0, 39, 118, 0.12), 0 8px 24px rgba(0, 39, 118, 0.1)',
            },
        },
    },
    plugins: [],
};
