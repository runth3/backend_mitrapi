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
                    <BaseButton
                        type="submit"
                        :loading="loading"
                        :disabled="loading"
                    >
                        Login
                    </BaseButton>
                </v-form>

                <!-- Error Alert -->
                <v-alert
                    v-if="error"
                    type="error"
                    class="mt-4"
                    dense
                    closable
                    @click:close="error = null"
                >
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
            error: null as string | null,
            loading: false,
        };
    },
    methods: {
        async handleLogin() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.post(
                    "/api/auth/login",
                    {
                        username: this.username,
                        password: this.password,
                    },
                    {
                        headers: {
                            Accept: "application/json",
                            "Content-Type": "application/json",
                        },
                    }
                );

                if (response.data.access_token) {
                    // Save the token
                    localStorage.setItem("token", response.data.access_token);

                    // Set default authorization header for future requests
                    axios.defaults.headers.common[
                        "Authorization"
                    ] = `Bearer ${response.data.access_token}`;

                    // Redirect to dashboard
                    this.$router.push({ name: "Dashboard" });
                } else {
                    this.error = "Invalid response from server";
                }
            } catch (err: any) {
                console.error("Login error:", err);
                this.error =
                    err.response?.data?.message ||
                    "Login failed. Please check your credentials and try again.";
            } finally {
                this.loading = false;
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
