<template>
    <v-container
        class="d-flex justify-center align-center pa-5"
        style="height: 100vh"
    >
        <v-card class="pa-5" max-width="600" elevation="3">
            <!-- Add Logo -->
            <v-img
                src="/images/logo.png"
                max-width="150"
                class="mx-auto mb-4"
            ></v-img>

            <v-card-title class="text-h5 text-center">Login</v-card-title>
            <v-card-text>
                <v-form @submit.prevent="handleLogin">
                    <!-- Username Field -->
                    <BaseInput v-model="username" label="Username"></BaseInput>

                    <!-- Password Field -->
                    <BaseInput
                        v-model="password"
                        label="Password"
                        type="password"
                    ></BaseInput>

                    <!-- Login Button -->
                    <BaseButton type="submit">Login</BaseButton>
                </v-form>

                <!-- Error Alert -->
                <v-alert v-if="error" type="error" class="mt-4" dense>
                    {{ error }}
                </v-alert>
            </v-card-text>
        </v-card>
    </v-container>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import axios from "axios";
import BaseInput from "@/components/BaseInput.vue";
import BaseButton from "@/components/BaseButton.vue";

export default defineComponent({
    name: "Login",
    components: {
        BaseInput,
        BaseButton,
    },
    data() {
        return {
            username: "",
            password: "",
            error: null,
        };
    },
    methods: {
        async handleLogin() {
            try {
                const response = await axios.post("/api/auth/login", {
                    username: this.username,
                    password: this.password,
                });

                // Save the token and redirect to the dashboard
                localStorage.setItem("auth_token", response.data.access_token);
                axios.defaults.headers.common[
                    "Authorization"
                ] = `Bearer ${response.data.access_token}`;
                this.$router.push({ name: "Dashboard" });
            } catch (err: any) {
                this.error =
                    err.response?.data?.message ||
                    "Login failed. Please try again.";
            }
        },
    },
});
</script>

<style scoped>
/* Add custom styles if needed */
.v-card-title {
    font-weight: bold;
    margin-bottom: 20px;
}

.v-btn {
    font-size: 16px;
    font-weight: bold;
}
</style>
