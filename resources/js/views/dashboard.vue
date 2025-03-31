<template>
    <v-container>
        <v-app-bar app color="primary" dark>
            <v-toolbar-title>Admin Dashboard</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn @click="logout" color="secondary">Logout</v-btn>
        </v-app-bar>
        <v-main>
            <v-container>
                <h1>Welcome to the Admin Dashboard</h1>
            </v-container>
        </v-main>
    </v-container>
</template>

<script>
import axios from "axios";

export default {
    methods: {
        async logout() {
            try {
                // Call the logout API
                await axios.post("/api/auth/logout");

                // Remove the token from localStorage
                localStorage.removeItem("auth_token");
                delete axios.defaults.headers.common["Authorization"];

                // Redirect to the login page
                this.$router.push({ name: "Login" });
            } catch (err) {
                console.error(
                    "Logout failed:",
                    err.response?.data?.message || err.message
                );
            }
        },
    },
};
</script>

<style scoped>
/* Add custom styles if needed */
</style>
