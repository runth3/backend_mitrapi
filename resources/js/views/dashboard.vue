<template>
    <default-layout title="Dashboard" :menu-items="menuItems" @logout="logout">
        <v-row>
            <v-col>
                <v-card>
                    <v-card-title>Welcome to the Dashboard</v-card-title>
                    <v-card-text>
                        This is a simple dashboard page. Customize it as needed.
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </default-layout>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import { menuItems } from "../config/menu";

interface MenuItem {
    title: string;
    icon: string;
    path: string;
}

export default defineComponent({
    name: "Dashboard",
    components: {
        DefaultLayout,
    },
    data() {
        return {
            menuItems,
        };
    },
    methods: {
        logout() {
            localStorage.removeItem("auth_token");
            this.$router.push({ name: "Login" });
        },
    },
});
</script>
