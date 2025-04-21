<template>
    <default-layout
        title="Dashboard"
        :menu-items="menuItems"
        @logout="handleLogout"
    >
        <v-row>
            <!-- Fake Stats Cards -->
            <v-col cols="12" sm="6" md="4">
                <BaseCard>
                    <v-card-title>
                        <v-icon left color="primary">mdi-account-group</v-icon>
                        Total Users
                    </v-card-title>
                    <v-card-text>
                        <h2 class="text-h4">1,245</h2>
                        <p class="text-caption">Active users in the system</p>
                    </v-card-text>
                </BaseCard>
            </v-col>
            <v-col cols="12" sm="6" md="4">
                <BaseCard>
                    <v-card-title>
                        <v-icon left color="primary">mdi-newspaper</v-icon>
                        Total News
                    </v-card-title>
                    <v-card-text>
                        <h2 class="text-h4">342</h2>
                        <p class="text-caption">Published news articles</p>
                    </v-card-text>
                </BaseCard>
            </v-col>
            <v-col cols="12" sm="6" md="4">
                <BaseCard>
                    <v-card-title>
                        <v-icon left color="primary"
                            >mdi-face-recognition</v-icon
                        >
                        Total Face Models
                    </v-card-title>
                    <v-card-text>
                        <h2 class="text-h4">789</h2>
                        <p class="text-caption">Registered face models</p>
                    </v-card-text>
                </BaseCard>
            </v-col>
            <!-- User Information Card -->
            <v-col cols="12" md="6">
                <BaseCard>
                    <v-card-title>User Information</v-card-title>
                    <v-card-text>
                        <p><strong>Name:</strong> {{ userName }}</p>
                        <p><strong>Email:</strong> {{ userEmail }}</p>
                        <p>
                            <strong>Role:</strong>
                            {{ isAdmin ? "Admin" : "User" }}
                        </p>
                    </v-card-text>
                </BaseCard>
            </v-col>
            <!-- Quick Actions Card -->
            <v-col cols="12" md="6">
                <BaseCard>
                    <v-card-title>Quick Actions</v-card-title>
                    <v-card-text>
                        <BaseButton
                            color="primary"
                            prepend-icon="mdi-newspaper"
                            to="/news"
                        >
                            View News
                        </BaseButton>
                        <BaseButton
                            v-if="isAdmin"
                            color="primary"
                            prepend-icon="mdi-account-group"
                            to="/users"
                            class="ml-2"
                        >
                            Manage Users
                        </BaseButton>
                    </v-card-text>
                </BaseCard>
            </v-col>
        </v-row>

        <!-- Welcome Snackbar -->
        <BaseSnackbar
            v-model="showSnackbar"
            color="success"
            timeout="5000"
            location="top right"
            closable
        >
            Welcome to Dashboard, {{ userName }}!
        </BaseSnackbar>
    </default-layout>
</template>

<script lang="ts">
import { defineComponent, computed, watch, ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import BaseButton from "@/components/BaseButton.vue";
import BaseCard from "@/components/BaseCard.vue";
import BaseSnackbar from "@/components/BaseSnackbar.vue"; // Impor BaseSnackbar
import { useAuth } from "@/composables/useAuth";
import { useUser } from "@/composables/useUser";
import { adminMenuItems, userMenuItems } from "@/config/menu";

export default defineComponent({
    name: "Dashboard",
    components: {
        DefaultLayout,
        BaseButton,
        BaseCard,
        BaseSnackbar, // Tambahkan BaseSnackbar
    },
    setup() {
        const router = useRouter();
        const { logout, isAdmin } = useAuth();
        const { userName, userEmail } = useUser();
        const showSnackbar = ref(false);

        const menuItems = computed(() => {
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

        onMounted(() => {
            showSnackbar.value = true; // Tampilkan snackbar saat halaman dimuat
        });

        watch(isAdmin, () => {
            console.log("isAdmin changed:", isAdmin.value);
        });

        return {
            menuItems,
            userName,
            userEmail,
            isAdmin,
            handleLogout,
            showSnackbar,
        };
    },
});
</script>

<style scoped>
.text-h4 {
    font-weight: bold;
}

.text-caption {
    color: var(--v-theme-on-surface-variant);
}
</style>
