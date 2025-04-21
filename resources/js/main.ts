import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";
import { createVuetify } from "vuetify";
import "vuetify/styles";

// Komponen Vuetify yang digunakan
import {
    VApp,
    VBtn,
    VCard,
    VCardText,
    VCardTitle,
    VContainer,
    VForm,
    VImg,
    VTextField,
    VAlert,
    VMain,
} from "vuetify/components";

// Vuetify
const vuetify = createVuetify({
    components: {
        VApp,
        VBtn,
        VCard,
        VCardText,
        VCardTitle,
        VContainer,
        VForm,
        VImg,
        VTextField,
        VAlert,
        VMain,
    },
    theme: {
        defaultTheme: "normal",
        themes: {
            normal: {
                dark: false,
                colors: {
                    primary: "#FF4D4D", // Merah Cerah Muda
                    secondary: "#FF8080", // Merah Lembut
                    accent: "#FF1A1A", // Merah Dalam
                    complementary: "#4D94FF", // Biru Cerah
                    success: "#4CAF50", // Hijau
                    warning: "#FFC107", // Kuning
                    error: "#D32F2F", // Merah Tua
                    background: "#F5F5F5", // Light Gray
                    surface: "#FFFFFF", // Putih untuk kartu
                    onPrimary: "#FFFFFF", // Teks di atas primary
                    onSecondary: "#333333", // Teks di atas secondary
                    onBackground: "#333333", // Teks di atas background
                    onSurface: "#333333", // Teks di atas surface
                    neutralMedium: "#8C8C8C", // Medium Gray
                    neutralDark: "#333333", // Dark Gray
                },
            },
            night: {
                dark: true,
                colors: {
                    primary: "#FF6666", // Merah Cerah Muda (Dark Mode)
                    secondary: "#FF9999", // Merah Pucat
                    accent: "#FF3333", // Merah Dalam
                    complementary: "#66B3FF", // Biru Cerah
                    success: "#66BB6A", // Hijau Lembut
                    warning: "#FFCA28", // Kuning
                    error: "#E53935", // Merah Tua
                    background: "#1A1A1A", // Dark Background
                    surface: "#2E2E2E", // Dark Gray untuk kartu
                    onPrimary: "#FFFFFF", // Teks di atas primary
                    onSecondary: "#E6E6E6", // Teks di atas secondary
                    onBackground: "#E6E6E6", // Teks di atas background
                    onSurface: "#E6E6E6", // Teks di atas surface
                    neutralLight: "#B3B3B3", // Light Gray
                    neutralDark: "#E6E6E6", // Near White
                },
            },
            singleTone: {
                dark: false,
                colors: {
                    primary: "#8C8C8C", // Medium Gray (monokromatik)
                    secondary: "#B3B3B3", // Light Gray (varian lebih terang)
                    accent: "#333333", // Dark Gray (kontras)
                    complementary: "#4D94FF", // Biru Cerah tetap digunakan
                    success: "#4CAF50", // Hijau
                    warning: "#FFC107", // Kuning
                    error: "#D32F2F", // Merah Tua
                    background: "#F5F5F5", // Light Gray
                    surface: "#FFFFFF", // Putih untuk kartu
                    onPrimary: "#FFFFFF", // Teks di atas primary
                    onSecondary: "#333333", // Teks di atas secondary
                    onBackground: "#333333", // Teks di atas background
                    onSurface: "#333333", // Teks di atas surface
                    neutralMedium: "#8C8C8C", // Medium Gray
                    neutralDark: "#333333", // Dark Gray
                },
            },
        },
    },
});

// Aplikasi
const app = createApp(App);
app.use(createPinia());
app.use(router);
app.use(vuetify);
app.mount("#app");
