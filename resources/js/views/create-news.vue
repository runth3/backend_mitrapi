<template>
    <default-layout
        title="Create News"
        :menu-items="menuItems"
        @logout="logout"
    >
        <v-row>
            <v-col cols="12" md="8" offset-md="2">
                <news-form
                    title="Create News"
                    :loading="loading"
                    @submit="handleSubmit"
                    @cancel="handleCancel"
                />
            </v-col>
        </v-row>

        <!-- Snackbar for notifications -->
        <v-snackbar
            v-model="snackbar"
            :color="snackbarColor"
            :timeout="3000"
            :top="true"
            :right="true"
        >
            {{ snackbarText }}
            <template v-slot:actions>
                <v-btn color="white" text @click="snackbar = false">
                    Close
                </v-btn>
            </template>
        </v-snackbar>
    </default-layout>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import axios from "axios";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import NewsForm from "../components/news/NewsForm.vue";
import { menuItems } from "../config/menu";

export default defineComponent({
    name: "CreateNews",
    components: {
        DefaultLayout,
        NewsForm,
    },
    data() {
        return {
            loading: false,
            menuItems,
            // Snackbar related data
            snackbar: false,
            snackbarText: "",
            snackbarColor: "success",
        };
    },
    methods: {
        showSnackbar(text: string, color: "success" | "error" = "success") {
            this.snackbarText = text;
            this.snackbarColor = color;
            this.snackbar = true;
        },

        async handleSubmit(formData: FormData) {
            this.loading = true;
            try {
                await axios.post("/api/news", formData, {
                    headers: {
                        Accept: "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                        // Don't set Content-Type for FormData
                    },
                });
                this.showSnackbar("News created successfully");
                this.$router.push({ name: "News" });
            } catch (error: any) {
                console.error("Failed to create news:", error);
                this.showSnackbar(
                    error.response?.data?.message || "Failed to create news",
                    "error"
                );
            } finally {
                this.loading = false;
            }
        },

        handleCancel() {
            this.$router.push({ name: "News" });
        },

        logout() {
            localStorage.removeItem("auth_token");
            this.$router.push({ name: "Login" });
        },
    },
});
</script>

<style scoped>
/* Add any custom styles here */
.v-snackbar {
    margin-top: 56px; /* Adjust if you have a different app bar height */
}
</style>
