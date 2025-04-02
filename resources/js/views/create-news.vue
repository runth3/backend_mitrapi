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
    </default-layout>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import axios from "axios";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import NewsForm, { NewsFormData } from "../components/news/NewsForm.vue";
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
        };
    },
    methods: {
        async handleSubmit(formData: NewsFormData) {
            this.loading = true;
            try {
                await axios.post("/api/news", formData);
                this.$router.push({ name: "News" });
            } catch (error) {
                console.error("Failed to create news:", error);
                // You might want to show an error message to the user here
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
