<template>
    <default-layout
        title="Create News"
        :menu-items="menuItems"
        @logout="handleLogout"
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
import { useRouter } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import NewsForm from "@/components/news/NewsForm.vue";
import BaseButton from "@/components/BaseButton.vue";
import { useAuth } from "@/composables/useAuth";
import { adminMenuItems, userMenuItems } from "@/config/menu";

export default defineComponent({
    name: "CreateNews",
    components: {
        DefaultLayout,
        NewsForm,
        BaseButton,
    },
    setup() {
        const router = useRouter();
        const { logout, isAdmin } = useAuth();
        const loading = ref(false);
        const snackbar = ref(false);
        const snackbarText = ref("");
        const snackbarColor = ref<"success" | "error">("success");
        const menuItems = ref(isAdmin.value ? adminMenuItems : userMenuItems);

        watch(isAdmin, (newValue) => {
            menuItems.value = newValue ? adminMenuItems : userMenuItems;
        });

        const showSnackbar = (
            text: string,
            color: "success" | "error" = "success"
        ) => {
            snackbarText.value = text;
            snackbarColor.value = color;
            snackbar.value = true;
        };

        const handleSubmit = async (formData: FormData) => {
            loading.value = true;
            try {
                await axios.post("/api/news", formData, {
                    headers: {
                        Accept: "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                showSnackbar("News created successfully");
                router.push({ name: "news" });
            } catch (error: any) {
                console.error("Failed to create news:", error);
                showSnackbar(
                    error.response?.data?.message || "Failed to create news",
                    "error"
                );
            } finally {
                loading.value = false;
            }
        };

        const handleCancel = () => {
            router.push({ name: "news" });
        };

        const handleLogout = async () => {
            try {
                await logout();
                router.push({ name: "Login" });
            } catch (err) {
                console.error("Logout failed:", err);
            }
        };

        return {
            menuItems,
            loading,
            snackbar,
            snackbarText,
            snackbarColor,
            handleSubmit,
            handleCancel,
            handleLogout,
        };
    },
});
</script>

<style scoped>
.v-snackbar {
    margin-top: 56px;
}
</style>
