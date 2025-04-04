<template>
    <default-layout
        title="News Detail"
        :menu-items="menuItems"
        @logout="logout"
    >
        <v-row>
            <v-col cols="12">
                <v-card v-if="news">
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
                            <strong>Published on:</strong> {{ news.created_at }}
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
import { menuItems } from "../config/menu";

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
    },
    data() {
        return {
            news: null as NewsDetail | null,
            loading: false,
            menuItems,
            snackbar: false,
            snackbarText: "",
            snackbarColor: "success",
            isAuthenticated: false,
        };
    },
    methods: {
        showSnackbar(text: string, color: "success" | "error" = "success") {
            this.snackbarText = text;
            this.snackbarColor = color;
            this.snackbar = true;
        },

        async checkAuth() {
            const token = localStorage.getItem("token");
            if (!token) {
                this.$router.push({ name: "Login" });
                return false;
            }

            try {
                const response = await axios.get("/api/user", {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${token}`,
                    },
                });

                if (response.data) {
                    this.isAuthenticated = true;
                    return true;
                }

                this.logout();
                return false;
            } catch (error) {
                console.error("Authentication check failed:", error);
                this.logout();
                return false;
            }
        },

        async fetchNewsDetail() {
            if (!(await this.checkAuth())) {
                return;
            }

            this.loading = true;
            try {
                const id = this.$route.params.id;
                const response = await axios.get(`/api/news/${id}`, {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                    },
                });
                this.news = response.data;
            } catch (error: any) {
                console.error("Failed to fetch news detail:", error);
                if (error.response?.status === 401) {
                    this.logout();
                    return;
                }
                this.showSnackbar(
                    error.response?.data?.message ||
                        "Failed to fetch news detail",
                    "error"
                );
            } finally {
                this.loading = false;
            }
        },

        logout() {
            localStorage.removeItem("token");
            this.$router.push({ name: "Login" });
        },

        async initializePage() {
            this.loading = true;
            try {
                const isAuthed = await this.checkAuth();
                if (isAuthed) {
                    await this.fetchNewsDetail();
                } else {
                    this.showSnackbar("Please login to continue", "error");
                    this.$router.push({ name: "Login" });
                }
            } catch (error) {
                console.error("Initialization error:", error);
                this.showSnackbar("Failed to initialize page", "error");
            } finally {
                this.loading = false;
            }
        },
    },
    async created() {
        await this.initializePage();
    },
    beforeRouteEnter(to: any, from: any, next: Function) {
        if (!localStorage.getItem("token")) {
            next({ name: "Login" });
        } else {
            next();
        }
    },
    // Handle route parameter changes without full component reload
    async beforeRouteUpdate(to: any, from: any, next: Function) {
        next();
        await this.fetchNewsDetail();
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
</style>
