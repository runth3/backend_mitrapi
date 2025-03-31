<template>
    <v-container
        class="d-flex justify-center align-center"
        style="height: 100vh"
    >
        <v-card class="pa-5" max-width="400">
            <v-card-title>Admin Login</v-card-title>
            <v-card-text>
                <v-form @submit.prevent="login">
                    <v-text-field
                        v-model="username"
                        label="Username"
                        required
                    ></v-text-field>
                    <v-text-field
                        v-model="password"
                        label="Password"
                        type="password"
                        required
                    ></v-text-field>
                    <v-btn type="submit" color="primary" block>Login</v-btn>
                </v-form>
                <v-alert v-if="error" type="error" class="mt-3">{{
                    error
                }}</v-alert>
            </v-card-text>
        </v-card>
    </v-container>
</template>

<script>
import axios from "axios";

export default {
    data() {
        return {
            username: "",
            password: "",
            error: null,
        };
    },
    methods: {
        async login() {
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
            } catch (err) {
                this.error =
                    err.response?.data?.message ||
                    "Login failed. Please try again.";
            }
        },
    },
};
</script>

<style scoped>
/* Add custom styles if needed */
</style>
