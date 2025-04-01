import defaultTheme from "tailwindcss/defaultTheme";

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/views/**/*.blade.php", // Laravel Blade templates
        "./resources/js/**/*.vue", // Vue components
        "./resources/js/**/*.js", // JavaScript files
        "./resources/js/**/*.ts", // TypeScript files
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};
