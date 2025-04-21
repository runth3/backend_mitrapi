<template>
    <div class="login-background">
        <v-container
            class="d-flex justify-center align-center pa-5"
            style="min-height: 100vh; height: 100%"
        >
            <BaseCard
                class="pa-5 login-card"
                max-width="600"
                width="100%"
                style="min-width: 400px"
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
                </v-card-text>
            </BaseCard>
        </v-container>
    </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from "vue";
import LoginForm from "@/components/LoginForm.vue";
import BaseCard from "@/components/BaseCard.vue"; // Impor BaseCard
import { useAuth } from "@/composables/useAuth";
import { useRouter } from "vue-router";

export default defineComponent({
    name: "Login",
    components: {
        LoginForm,
        BaseCard, // Tambahkan BaseCard
    },
    setup() {
        const router = useRouter();
        const username = ref("");
        const password = ref("");
        const { login, error, loading } = useAuth();

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

.v-card-title {
    font-weight: bold;
    margin-bottom: 20px;
}
</style>
