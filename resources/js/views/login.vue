<template>
    <div class="login-background">
        <v-container
            class="d-flex justify-center align-center pa-5"
            style="min-height: 100vh; height: 100%"
        >
            <v-card class="pa-5 login-card" max-width="600" elevation="3">
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
                        <BaseInput
                            v-model="username"
                            label="Username"
                        ></BaseInput>

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
    </div>
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

                    // Fetch user profile
                    await this.fetchUserProfile();

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
        async fetchUserProfile() {
            try {
                const response = await axios.get("/api/profile/me", {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                    },
                });
                console.log(response.data);
                // Store user data in local storage
                localStorage.setItem(
                    "userData",
                    JSON.stringify(response.data.user)
                );
            } catch (error) {
                console.error("Failed to fetch user profile:", error);
            }
        },
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
    /* Add filter to dim the background */
    &::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.3); /* Adjust opacity as needed */
        z-index: 1;
    }
}

/* Adjust container to be above the overlay */
.v-container {
    position: relative;
    z-index: 2;
}

.login-card {
    position: relative;
    z-index: 2;
    background-color: rgba(255, 255, 255, 0.9);
}

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
