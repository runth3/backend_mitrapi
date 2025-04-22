<template>
    <default-layout
        title="News Detail"
        :menu-items="menuItems"
        @logout="handleLogout"
    >
        <v-row>
            <v-col cols="12">
                <v-card class="dashboard-card" elevation="3" v-if="news">
                    <v-card-title>{{ news.title }}</v-card-title>
                    <v-card-text>
                        <v-img
                            v-if="news.image_url"
                            :src="news.image_url"
                            height="300"
                            cover
                            class="mb-4"
                        ></v-img>
                        <p>{{ news.content }}</p>
                        <p>
                            <strong>Published on:</strong>
                            {{ formatDate(news.created_at) }}
                        </p>
                    </v-card-text>
                </v-card>
                <v-skeleton-loader
                    v-else-if="loading"
                    type="article"
                ></v-skeleton-loader>
            </v-col>
        </v-row>
        <v-snackbar
            v-model="snackbar"
            :color="snackbarColor"
            :timeout="3000"
            location="top"
            vertical
        >
            {{ snackbarText }}
            <template #actions>
                <BaseButton
                    color="white"
                    variant="text"
                    @click="snackbar = false"
                >
                    Close
                </BaseButton>
            </template>
        </v-snackbar>
    </default-layout>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from "vue";
import axios from "axios";
import { useRoute, useRouter } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import BaseButton from "@/components/BaseButton.vue";
import { useAuth } from "@/composables/useAuth";
import { adminMenuItems, userMenuItems } from "@/config/menu";

interface NewsDetail {
    id: number;
    title: string;
    content: string;
    image_url?: string;
    created_at: string;
}

export default defineComponent({
    name: "NewsDetail",
    components: {
        DefaultLayout,
        BaseButton,
    },
    setup() {
        const route = useRoute();
        const router = useRouter();
        const { logout, isAdmin } = useAuth();
        const news = ref<NewsDetail | null>(null);
        const loading = ref(false);
        const snackbar = ref(false);
        const snackbarText = ref("");
        const snackbarColor = ref<"success" | "error">("success");
        const menuItems = ref(isAdmin.value ? adminMenuItems : userMenuItems);

        watch(isAdmin, (newValue) => {
            menuItems.value = newValue ? adminMenuItems : userMenuItems;
        });

        const formatDate = (dateString: string) => {
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        };

        const showSnackbar = (
            text: string,
            color: "success" | "error" = "success"
        ) => {
            snackbarText.value = text;
            snackbarColor.value = color;
            snackbar.value = true;
        };

        const fetchNewsDetail = async () => {
            loading.value = true;
            try {
                const id = route.params.id;
                const response = await axios.get(`/api/news/${id}`, {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                news.value = response.data;
            } catch (error: any) {
                console.error("Failed to fetch news detail:", error);
                if (error.response?.status === 401) {
                    handleLogout();
                    return;
                }
                showSnackbar(
                    error.response?.data?.message ||
                        "Failed to fetch news detail",
                    "error"
                );
            } finally {
                loading.value = false;
            }
        };

        const handleLogout = async () => {
            try {
                await logout();
                router.push({ name: "Login" });
            } catch (err) {
                console.error("Logout failed:", err);
            }
        };

        const initializePage = async () => {
            const token = localStorage.getItem("auth_token");
            if (!token) {
                showSnackbar("Please login to continue", "error");
                router.push({ name: "Login" });
                return;
            }
            await fetchNewsDetail();
        };

        initializePage();

        watch(
            () => route.params.id,
            () => {
                fetchNewsDetail();
            }
        );

        return {
            menuItems,
            news,
            loading,
            snackbar,
            snackbarText,
            snackbarColor,
            handleLogout,
            formatDate,
        };
    },
});
</script>

<style scoped>
.v-snackbar {
    margin-top: 56px;
}

.v-card-text {
    white-space: pre-line;
}

/* Background card dinamis berdasarkan tema */
:root[data-theme="normal"] .dashboard-card,
:root[data-theme="singleTone"] .dashboard-card {
    background-color: rgba(255, 255, 255, 0.9);
}
:root[data-theme="night"] .dashboard-card {
    background-color: rgba(46, 46, 46, 0.9);
}
</style>
