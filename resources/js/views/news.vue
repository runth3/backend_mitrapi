<template>
    <default-layout title="News" :menu-items="menuItems" @logout="handleLogout">
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
import { defineComponent, ref, watch } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import NewsList, { NewsItem } from "@/components/news/NewsList.vue";
import { useAuth } from "@/composables/useAuth";
import { adminMenuItems, userMenuItems } from "@/config/menu";

export default defineComponent({
    name: "News",
    components: {
        DefaultLayout,
        NewsList,
    },
    setup() {
        const router = useRouter();
        const { logout, isAdmin } = useAuth();
        const news = ref<NewsItem[]>([]);
        const loading = ref(false);
        const currentPage = ref(1);
        const totalPages = ref(1);
        const perPage = ref(20);
        const menuItems = ref(isAdmin.value ? adminMenuItems : userMenuItems);

        watch(isAdmin, (newValue) => {
            menuItems.value = newValue ? adminMenuItems : userMenuItems;
        });

        const fetchNews = async (page = 1) => {
            try {
                loading.value = true;
                const response = await axios.get("/api/news", {
                    params: {
                        page,
                        per_page: perPage.value,
                    },
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                if (Array.isArray(response.data.data)) {
                    news.value = response.data.data;
                    currentPage.value = response.data.current_page;
                    totalPages.value = response.data.last_page;
                } else {
                    console.error("Invalid response format:", response.data);
                    news.value = [];
                }
            } catch (error) {
                console.error("Failed to fetch news:", error);
                news.value = [];
            } finally {
                loading.value = false;
            }
        };

        const handlePageChange = (page: number) => {
            fetchNews(page);
        };

        const viewNews = (id: number) => {
            router.push({ name: "newsDetail", params: { id } });
        };

        const createNews = () => {
            router.push({ name: "createNews" });
        };

        const handleLogout = async () => {
            try {
                await logout();
                router.push({ name: "Login" });
            } catch (err) {
                console.error("Logout failed:", err);
            }
        };

        fetchNews();

        return {
            menuItems,
            news,
            loading,
            currentPage,
            totalPages,
            handlePageChange,
            viewNews,
            createNews,
            handleLogout,
        };
    },
});
</script>
