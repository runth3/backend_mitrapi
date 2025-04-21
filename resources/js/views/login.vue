<template>
    <div class="login-background">
        <v-container
            class="d-flex justify-center align-center pa-5"
            style="min-height: 100vh; height: 100%"
        >
            <v-card
                class="pa-5 login-card"
                max-width="600"
                width="100%"
                style="min-width: 400px"
                elevation="3"
            >
                <v-img
                    src="/images/logo.png"
                    max-width="150"
                    class="mx-auto mb-4"
                />
                <v-card-title class="text-h5 text-center">Login</v-card-title>
                <v-card-text>
                    <LoginForm
                        :username="username"
                        @update:username="username = $event"
                        :password="password"
                        @update:password="password = $event"
                        :error="error"
                        @update:error="error = $event"
                        :loading="loading"
                        @submit="handleLogin"
                    />
                    <!-- Tombol tema untuk pengujian -->
                    <div class="mt-4 d-flex justify-center">
                        <v-tooltip location="top">
                            <template v-slot:activator="{ props }">
                                <BaseButton
                                    v-bind="props"
                                    size="small"
                                    color="primary"
                                    variant="tonal"
                                    prepend-icon="mdi-palette"
                                    rounded="lg"
                                    class="theme-button mr-2"
                                    aria-label="Normal Theme"
                                    @click="setTheme('normal')"
                                >
                                    <span class="visually-hidden"
                                        >Normal Theme</span
                                    >
                                </BaseButton>
                            </template>
                            <span>Normal Theme</span>
                        </v-tooltip>
                        <v-tooltip location="top">
                            <template v-slot:activator="{ props }">
                                <BaseButton
                                    v-bind="props"
                                    size="small"
                                    color="primary"
                                    variant="tonal"
                                    prepend-icon="mdi-moon-waning-crescent"
                                    rounded="lg"
                                    class="theme-button mr-2"
                                    aria-label="Night Theme"
                                    @click="setTheme('night')"
                                >
                                    <span class="visually-hidden"
                                        >Night Theme</span
                                    >
                                </BaseButton>
                            </template>
                            <span>Night Theme</span>
                        </v-tooltip>
                        <v-tooltip location="top">
                            <template v-slot:activator="{ props }">
                                <BaseButton
                                    v-bind="props"
                                    size="small"
                                    color="primary"
                                    variant="tonal"
                                    prepend-icon="mdi-contrast-circle"
                                    rounded="lg"
                                    class="theme-button"
                                    aria-label="Single Tone Theme"
                                    @click="setTheme('singleTone')"
                                >
                                    <span class="visually-hidden"
                                        >Single Tone Theme</span
                                    >
                                </BaseButton>
                            </template>
                            <span>Single Tone Theme</span>
                        </v-tooltip>
                    </div>
                </v-card-text>
            </v-card>
        </v-container>
    </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from "vue";
import LoginForm from "@/components/LoginForm.vue";
import BaseButton from "@/components/BaseButton.vue";
import { useAuth } from "@/composables/useAuth";
import { useAppTheme } from "@/composables/useTheme";
import { useRouter } from "vue-router";

export default defineComponent({
    name: "Login",
    components: {
        LoginForm,
        BaseButton,
    },
    setup() {
        const router = useRouter();
        const username = ref("");
        const password = ref("");
        const { login, error, loading } = useAuth();
        const { setTheme } = useAppTheme();

        onMounted(() => {
            const token = localStorage.getItem("auth_token");
            console.log("onMounted: auth_token exists:", !!token);
            if (token) {
                console.log("Redirecting to /dashboard from onMounted");
                router.push("/dashboard");
            }
        });

        async function handleLogin() {
            console.log("handleLogin called with username:", username.value);
            try {
                await login(username.value, password.value);
                console.log(
                    "Login successful, should be redirected by useAuth"
                );
            } catch (err) {
                console.error("Login failed in handleLogin:", err);
            }
        }

        return {
            username,
            password,
            error,
            loading,
            handleLogin,
            setTheme,
        };
    },
});
</script>

<style scoped>
.login-background {
    background-image: url("/images/background.jpg");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    min-height: 100vh;
}
.login-background::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.3);
    z-index: 1;
}

.v-container {
    position: relative;
    z-index: 2;
}

.login-card {
    position: relative;
    z-index: 2;
}
:root[data-theme="normal"] .login-card,
:root[data-theme="singleTone"] .login-card {
    background-color: rgba(255, 255, 255, 0.9);
}
:root[data-theme="night"] .login-card {
    background-color: rgba(46, 46, 46, 0.9);
}

.v-card-title {
    font-weight: bold;
    margin-bottom: 20px;
}

.theme-button {
    min-width: 40px;
}

.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
</style>
