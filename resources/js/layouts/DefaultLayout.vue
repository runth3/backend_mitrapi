<template>
    <v-app>
        <!-- Top App Bar -->
        <v-app-bar color="primary" app>
            <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
            <v-toolbar-title>{{ title }}</v-toolbar-title>
            <v-spacer></v-spacer>

            <!-- User Profile Menu -->
            <v-menu>
                <template v-slot:activator="{ props }">
                    <v-btn icon v-bind="props">
                        <BaseAvatar />
                    </v-btn>
                </template>
                <v-list>
                    <v-list-item>
                        <v-list-item-title>{{ userName }}</v-list-item-title>
                        <v-list-item-subtitle>{{
                            userEmail
                        }}</v-list-item-subtitle>
                    </v-list-item>
                    <v-divider></v-divider>
                    <v-list-item>
                        <v-list-item-title>Theme</v-list-item-title>
                        <v-list-item-subtitle>
                            <ThemeSelector />
                        </v-list-item-subtitle>
                    </v-list-item>
                    <v-divider></v-divider>
                    <v-list-item>
                        <BaseButton
                            color="error"
                            variant="text"
                            prepend-icon="mdi-logout"
                            @click="handleLogout"
                        >
                            Logout
                        </BaseButton>
                    </v-list-item>
                </v-list>
            </v-menu>
        </v-app-bar>

        <Sidebar :menu-items="activeMenuItems" v-model="drawer" />

        <v-main>
            <v-container fluid>
                <slot></slot>
            </v-container>
        </v-main>
    </v-app>
</template>

<script lang="ts">
import { defineComponent, ref, computed } from "vue";
import { useRouter } from "vue-router";
import Sidebar from "@/components/Sidebar.vue";
import BaseButton from "@/components/BaseButton.vue";
import BaseAvatar from "@/components/BaseAvatar.vue"; // Impor BaseAvatar
import ThemeSelector from "@/components/ThemeSelector.vue";
import { useAuth } from "@/composables/useAuth";
import { useAppTheme } from "@/composables/useTheme";
import { useUser } from "@/composables/useUser";
import { adminMenuItems, userMenuItems } from "@/config/menu";

interface MenuItem {
    title: string;
    icon: string;
    path: string;
}

export default defineComponent({
    name: "DefaultLayout",
    components: {
        Sidebar,
        BaseButton,
        BaseAvatar, // Tambahkan BaseAvatar
        ThemeSelector,
    },
    props: {
        title: {
            type: String,
            default: "Dashboard",
        },
        menuItems: {
            type: Array as () => MenuItem[],
            required: true,
        },
    },
    setup() {
        const router = useRouter();
        const { logout, isAdmin } = useAuth();
        const { currentTheme } = useAppTheme();
        const { userName, userEmail } = useUser();
        const drawer = ref(true);

        const isDarkTheme = computed(() => currentTheme.value === "night");

        const activeMenuItems = computed(() => {
            return isAdmin.value ? adminMenuItems : userMenuItems;
        });

        async function handleLogout() {
            try {
                await logout();
                router.push({ name: "Login" });
            } catch (err) {
                console.error("Logout failed:", err);
            }
        }

        return {
            drawer,
            userName,
            userEmail,
            activeMenuItems,
            isDarkTheme,
            handleLogout,
        };
    },
});
</script>
