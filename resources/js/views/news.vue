<template>
    <default-layout title="News" :menu-items="menuItems" @logout="logout">
        <v-row>
            <v-col cols="12">
                <news-list
                    :items="news"
                    :loading="loading"
                    :total-pages="totalPages"
                    :current-page="currentPage"
                    @view="viewNews"
                    @create="createNews"
                    @page-change="handlePageChange"
                />
            </v-col>
        </v-row>
    </default-layout>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import axios from "axios";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import NewsList, { NewsItem } from "../components/news/NewsList.vue";
import { menuItems } from "../config/menu";

export default defineComponent({
    name: "News",
    components: {
        DefaultLayout,
        NewsList,
    },
    data() {
        return {
            news: [] as NewsItem[],
            menuItems,
            loading: false,
            currentPage: 1,
            totalPages: 1,
            perPage: 20,
        };
    },
    methods: {
        async fetchNews(page = 1) {
            try {
                this.loading = true;
                const response = await axios.get("/api/news", {
                    params: {
                        page,
                        per_page: this.perPage,
                    },
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                    },
                });
                console.log("API Response:", response.data);
                if (Array.isArray(response.data.data)) {
                    this.news = response.data.data;
                    this.currentPage = response.data.current_page;
                    this.totalPages = response.data.last_page;
                } else {
                    console.error("Invalid response format:", response.data);
                    this.news = [];
                }
            } catch (error) {
                console.error("Failed to fetch news:", error);
                this.news = [];
            } finally {
                this.loading = false;
            }
        },
        handlePageChange(page: number) {
            this.fetchNews(page);
        },
        viewNews(id: number) {
            this.$router.push({ name: "NewsDetail", params: { id } });
        },
        createNews() {
            this.$router.push({ name: "CreateNews" });
        },
        logout() {
            localStorage.removeItem("auth_token");
            this.$router.push({ name: "Login" });
        },
    },
    mounted() {
        this.fetchNews();
    },
});
</script>
