import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";
import laravel from "laravel-vite-plugin";
import vuetify from "vite-plugin-vuetify"; // Import plugin Vuetify

export default defineConfig({
    server: {
        port: 3003,
    },
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/main.ts"],
            refresh: true,
        }),
        vue(),
        vuetify({ autoimport: true }), // Gunakan plugin Vuetify dengan auto-import
    ],
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "./resources/js"),
        },
    },
    optimizeDeps: {
        exclude: ["vuetify"], // Ganti include dengan exclude karena plugin akan menanganinya
    },
});
