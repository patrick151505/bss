module.exports = {
    content: [
        "node_modules/@frostui/tailwindcss/**/*.js",
        // Laravel's pagination templates (used by ->links()). Not the compiled view cache in
        // storage/framework/views: `php artisan view:clear` deletes those files while
        // `npm run dev` is watching them, which crashes the CSS build.
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    darkMode: ['class', '[data-mode="dark"]'],
    theme: {

        container: {
            center: true,
        },

        fontFamily: {
            'base': ['Inter', 'sans-serif'],
        },

        extend: {
            colors: {
                'primary': '#3073F1',

                'secondary': '#68625D',

                'success': '#1CB454',

                'warning': '#E2A907',

                'info': '#0895D8',

                'danger': '#E63535',

                'light': '#eef2f7',
                'dark': '#313a46',
            },
        },
    },

    plugins: [
        require('@frostui/tailwindcss/plugin'),
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('@tailwindcss/aspect-ratio'),

    ],
}
