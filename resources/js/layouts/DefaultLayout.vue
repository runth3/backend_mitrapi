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
                        <v-avatar size="32" color="grey-darken-1">
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
                    <v-list-item @click="handleLogout">
                        <v-list-item-title>Logout</v-list-item-title>
                        <template v-slot:prepend>
                            <v-icon>mdi-logout</v-icon>
                        </template>
                    </v-list-item>
                </v-list>
            </v-menu>
        </v-app-bar>

        <!-- Sidebar -->
        <Sidebar :menu-items="menuItems" v-model="drawer" />

        <!-- Main Content -->
        <v-main>
            <v-container fluid>
                <slot></slot>
            </v-container>
        </v-main>
    </v-app>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import Sidebar from "@/components/Sidebar.vue";

interface MenuItem {
    title: string;
    icon: string;
    path: string;
}

export default defineComponent({
    name: "DefaultLayout",
    components: {
        Sidebar,
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
    data() {
        return {
            drawer: true,
            userName: "",
            userEmail: "",
        };
    },
    methods: {
        handleLogout() {
            localStorage.removeItem("token");
            localStorage.removeItem("userData");
            this.$emit("logout");
        },
        loadUserData() {
            const userData = localStorage.getItem("userData");
            if (userData) {
                const parsedUserData = JSON.parse(userData);
                this.userName = parsedUserData.name;
                this.userEmail = parsedUserData.email;
            }
        },
    },
    mounted() {
        this.loadUserData();
    },
});
</script>

<style scoped>
.v-navigation-drawer {
    background-color: var(--v-theme-background);
}

.v-list-item--active {
    background-color: var(--v-theme-primary-lighten-2);
}
</style>
