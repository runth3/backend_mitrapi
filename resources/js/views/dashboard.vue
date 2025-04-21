<template>
    <default-layout
        title="Dashboard"
        :menu-items="menuItems"
        @logout="handleLogout"
    >
        <v-row>
            <v-col>
                <v-card class="dashboard-card" elevation="3">
                    <v-card-title class="text-h5"
                        >Welcome to the Dashboard</v-card-title
                    >
                    <v-card-text>
                        This is a simple dashboard page. Customize it as needed.
                        <div class="mt-4">
                            <BaseButton
                                color="error"
                                prepend-icon="mdi-logout"
                                rounded="lg"
                                @click="handleLogout"
                            >
                                Logout
                            </BaseButton>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </default-layout>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from "vue";
import { useRouter } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import BaseButton from "@/components/BaseButton.vue";
import { useAuth } from "@/composables/useAuth";
import { adminMenuItems, userMenuItems } from "@/config/menu";

interface MenuItem {
    title: string;
    icon: string;
    path: string;
}

export default defineComponent({
    name: "Dashboard",
    components: {
        DefaultLayout,
        BaseButton,
    },
    setup() {
        const router = useRouter();
        const { logout, isAdmin } = useAuth();
        const menuItems = ref<MenuItem[]>(
            isAdmin.value ? adminMenuItems : userMenuItems
        );

        // Watch perubahan isAdmin untuk memperbarui menu
        watch(isAdmin, (newValue) => {
            menuItems.value = newValue ? adminMenuItems : userMenuItems;
        });

        // Handle logout
        async function handleLogout() {
            try {
                await logout();
                router.push({ name: "Login" });
            } catch (err) {
                console.error("Logout failed:", err);
            }
        }

        return {
            menuItems,
            handleLogout,
        };
    },
});
</script>

<style scoped>
/* Background card dinamis berdasarkan tema */
:root[data-theme="normal"] .dashboard-card,
:root[data-theme="singleTone"] .dashboard-card {
    background-color: rgba(255, 255, 255, 0.9);
}
:root[data-theme="night"] .dashboard-card {
    background-color: rgba(46, 46, 46, 0.9); /* #2E2E2E dengan opacity 0.9 */
}
</style>
