import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    server: {
        port: 3003, // Change to a different port
    },
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.ts"],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "./resources/js"),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                additionalData: `@import "@mdi/font/css/materialdesignicons.min.css";`,
            },
        },
    },
});
