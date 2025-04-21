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
                        <v-avatar
                            size="32"
                            :color="
                                isDarkTheme ? 'grey-darken-3' : 'grey-darken-1'
                            "
                        >
                            <v-icon size="32">mdi-account</v-icon>
                        </v-avatar>
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
                    <!-- Theme Selection -->
                    <v-list-item>
                        <v-list-item-title>Theme</v-list-item-title>
                        <v-list-item-subtitle>
                            <div class="d-flex justify-start">
                                <v-tooltip location="top">
                                    <template v-slot:activator="{ props }">
                                        <BaseButton
                                            v-bind="props"
                                            size="small"
                                            color="primary"
                                            variant="tonal"
                                            prepend-icon="mdi-palette"
                                            rounded="lg"
                                            class="theme-button mr-2"
                                            aria-label="Normal Theme"
                                            @click="setTheme('normal')"
                                        >
                                            <span class="visually-hidden"
                                                >Normal Theme</span
                                            >
                                        </BaseButton>
                                    </template>
                                    <span>Normal Theme</span>
                                </v-tooltip>
                                <v-tooltip location="top">
                                    <template v-slot:activator="{ props }">
                                        <BaseButton
                                            v-bind="props"
                                            size="small"
                                            color="primary"
                                            variant="tonal"
                                            prepend-icon="mdi-moon-waning-crescent"
                                            rounded="lg"
                                            class="theme-button mr-2"
                                            aria-label="Night Theme"
                                            @click="setTheme('night')"
                                        >
                                            <span class="visually-hidden"
                                                >Night Theme</span
                                            >
                                        </BaseButton>
                                    </template>
                                    <span>Night Theme</span>
                                </v-tooltip>
                                <v-tooltip location="top">
                                    <template v-slot:activator="{ props }">
                                        <BaseButton
                                            v-bind="props"
                                            size="small"
                                            color="primary"
                                            variant="tonal"
                                            prepend-icon="mdi-contrast-circle"
                                            rounded="lg"
                                            class="theme-button"
                                            aria-label="Single Tone Theme"
                                            @click="setTheme('singleTone')"
                                        >
                                            <span class="visually-hidden"
                                                >Single Tone Theme</span
                                            >
                                        </BaseButton>
                                    </template>
                                    <span>Single Tone Theme</span>
                                </v-tooltip>
                            </div>
                        </v-list-item-subtitle>
                    </v-list-item>
                    <v-divider></v-divider>
                    <!-- Logout Button -->
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

        <!-- Sidebar -->
        <Sidebar :menu-items="activeMenuItems" v-model="drawer" />

        <!-- Main Content -->
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
import { useAuth } from "@/composables/useAuth";
import { useAppTheme } from "@/composables/useTheme";
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
        const { currentTheme, setTheme } = useAppTheme();
        const drawer = ref(true);
        const userName = ref("");
        const userEmail = ref("");

        // Computed untuk tema gelap
        const isDarkTheme = computed(() => currentTheme.value === "night");

        // Computed untuk menu berdasarkan role
        const activeMenuItems = computed(() => {
            return isAdmin.value ? adminMenuItems : userMenuItems;
        });

        // Mengambil data user dari localStorage
        function loadUserData() {
            const userData = localStorage.getItem("userData");
            if (userData) {
                const parsedUserData = JSON.parse(userData);
                userName.value = parsedUserData.name || "Unknown User";
                userEmail.value = parsedUserData.email || "No Email";
            } else {
                userName.value = "Unknown User";
                userEmail.value = "No Email";
            }
        }

        // Handle logout
        async function handleLogout() {
            try {
                await logout();
                router.push({ name: "Login" });
            } catch (err) {
                console.error("Logout failed:", err);
            }
        }

        // Load user data saat komponen dimount
        loadUserData();

        return {
            drawer,
            userName,
            userEmail,
            activeMenuItems,
            isDarkTheme,
            handleLogout,
            setTheme,
        };
    },
});
</script>

<style scoped>
.v-navigation-drawer {
    background-color: var(--v-theme-background);
}

.v-list-item--active {
    background-color: var(--v-theme-primary-lighten-2);
    color: var(--v-theme-on-primary);
}

.theme-button {
    min-width: 40px;
}

.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
</style>
