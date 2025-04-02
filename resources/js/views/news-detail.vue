<!-- filepath: /Users/randihartono/Desktop/src/mitrapi_xg/laravel/resources/js/views/news-detail.vue -->
<template>
    <default-layout
        title="News Detail"
        :menu-items="menuItems"
        @logout="logout"
    >
        <v-row>
            <v-col cols="12">
                <v-card>
                    <v-card-title>{{ news.title }}</v-card-title>
                    <v-card-text>
                        <p>{{ news.content }}</p>
                        <p>
                            <strong>Published on:</strong> {{ news.created_at }}
                        </p>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </default-layout>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import axios from "axios";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import { menuItems } from "../config/menu";

interface NewsDetail {
    id: number;
    title: string;
    content: string;
    created_at: string;
}

export default defineComponent({
    name: "NewsDetail",
    components: {
        DefaultLayout,
    },
    data() {
        return {
            news: {} as NewsDetail,
            menuItems,
        };
    },
    methods: {
        async fetchNewsDetail() {
            try {
                const id = this.$route.params.id;
                const response = await axios.get(`/api/news/${id}`);
                this.news = response.data;
            } catch (error) {
                console.error("Failed to fetch news detail:", error);
            }
        },
        logout() {
            localStorage.removeItem("auth_token");
            this.$router.push({ name: "Login" });
        },
    },
    mounted() {
        this.fetchNewsDetail();
    },
});
</script>
