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
                        <v-avatar size="32">
                            <v-img :src="userAvatar" :alt="userName"></v-img>
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
                        <v-list-item-icon>
                            <v-icon>mdi-logout</v-icon>
                        </v-list-item-icon>
                    </v-list-item>
                </v-list>
            </v-menu>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <v-navigation-drawer v-model="drawer" app>
            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title class="text-h6">
                        Menu
                    </v-list-item-title>
                </v-list-item-content>
            </v-list-item>

            <v-divider></v-divider>

            <v-list density="compact" nav>
                <v-list-item
                    v-for="item in menuItems"
                    :key="item.title"
                    :to="item.path"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    :value="item.title"
                    @click="handleNavigation(item)"
                    :active="currentRoute === item.path"
                ></v-list-item>
            </v-list>
        </v-navigation-drawer>

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

interface MenuItem {
    title: string;
    icon: string;
    path: string;
}

export default defineComponent({
    name: "DefaultLayout",
    props: {
        title: {
            type: String,
            default: "Dashboard",
        },
        menuItems: {
            type: Array as () => MenuItem[],
            required: true,
        },
        userName: {
            type: String,
            default: "John Doe",
        },
        userEmail: {
            type: String,
            default: "john@example.com",
        },
        userAvatar: {
            type: String,
            default: "https://cdn.vuetifyjs.com/images/john.jpg",
        },
    },
    data() {
        return {
            drawer: true,
            currentRoute: this.$route.path,
        };
    },
    methods: {
        handleNavigation(item: MenuItem) {
            this.currentRoute = item.path;
            this.$router.push(item.path);
        },
        handleLogout() {
            this.$emit("logout");
        },
    },
    watch: {
        $route(to) {
            this.currentRoute = to.path;
        },
    },
});
</script>

<style scoped>
.v-navigation-drawer {
    background-color: var(--v-background);
}

.v-list-item--active {
    background-color: var(--v-primary-lighten-2);
}
</style>
