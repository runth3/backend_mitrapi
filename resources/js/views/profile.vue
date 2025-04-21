<template>
    <default-layout
        title="Profile"
        :menu-items="menuItems"
        @logout="handleLogout"
    >
        <v-row>
            <v-col cols="12" md="6" offset-md="3">
                <v-card class="dashboard-card" elevation="3">
                    <v-card-title>Profile Settings</v-card-title>
                    <v-card-text>
                        <v-progress-linear
                            v-if="loading"
                            indeterminate
                            color="primary"
                        ></v-progress-linear>
                        <v-form
                            v-else
                            ref="form"
                            v-model="valid"
                            @submit.prevent="saveProfile"
                        >
                            <v-text-field
                                v-model="profile.name"
                                label="Name"
                                :rules="[(v) => !!v || 'Name is required']"
                                required
                            ></v-text-field>
                            <v-text-field
                                v-model="profile.email"
                                label="Email"
                                :rules="[
                                    (v) => !!v || 'Email is required',
                                    (v) =>
                                        /.+@.+\..+/.test(v) ||
                                        'Email must be valid',
                                ]"
                                required
                            ></v-text-field>
                            <v-text-field
                                v-model="profile.username"
                                label="Username"
                                :rules="[(v) => !!v || 'Username is required']"
                                required
                            ></v-text-field>
                            <v-text-field
                                v-model="profile.phone"
                                label="Phone"
                            ></v-text-field>
                            <v-text-field
                                v-model="profile.dob"
                                label="Date of Birth"
                                type="date"
                            ></v-text-field>
                            <v-text-field
                                v-model="profile.address"
                                label="Address"
                            ></v-text-field>
                            <v-text-field
                                v-model="profile.current_password"
                                label="Current Password"
                                type="password"
                                :rules="[
                                    (v) =>
                                        !v ||
                                        v.length >= 8 ||
                                        'Password must be at least 8 characters',
                                ]"
                            ></v-text-field>
                            <v-text-field
                                v-model="profile.new_password"
                                label="New Password"
                                type="password"
                                :rules="[
                                    (v) =>
                                        !v ||
                                        v.length >= 8 ||
                                        'Password must be at least 8 characters',
                                ]"
                            ></v-text-field>
                            <v-text-field
                                v-model="profile.new_password_confirmation"
                                label="Confirm New Password"
                                type="password"
                                :rules="[
                                    (v) =>
                                        !v ||
                                        v === profile.new_password ||
                                        'Passwords must match',
                                ]"
                            ></v-text-field>
                            <BaseButton
                                color="primary"
                                type="submit"
                                :loading="saving"
                                :disabled="!valid"
                            >
                                Save Changes
                            </BaseButton>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </default-layout>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import BaseButton from "@/components/BaseButton.vue";
import { useAuth } from "@/composables/useAuth";
import { adminMenuItems, userMenuItems } from "@/config/menu";

interface Profile {
    name: string;
    email: string;
    username: string;
    phone?: string;
    dob?: string;
    address?: string;
    current_password?: string;
    new_password?: string;
    new_password_confirmation?: string;
}

export default defineComponent({
    name: "Profile",
    components: {
        DefaultLayout,
        BaseButton,
    },
    setup() {
        const router = useRouter();
        const { logout, isAdmin } = useAuth();
        const loading = ref(false);
        const saving = ref(false);
        const valid = ref(false);
        const form = ref(null);
        const profile = ref<Profile>({
            name: "",
            email: "",
            username: "",
            phone: "",
            dob: "",
            address: "",
            current_password: "",
            new_password: "",
            new_password_confirmation: "",
        });
        const menuItems = ref(isAdmin.value ? adminMenuItems : userMenuItems);

        watch(isAdmin, (newValue) => {
            menuItems.value = newValue ? adminMenuItems : userMenuItems;
        });

        const fetchProfile = async () => {
            try {
                loading.value = true;
                const response = await axios.get("/api/profile/me", {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                profile.value.name = response.data.user.name;
                profile.value.email = response.data.user.email;
                profile.value.username = response.data.user.username;
                profile.value.phone = response.data.user.phone;
                profile.value.dob = response.data.user.dob;
                profile.value.address = response.data.user.address;
            } catch (error) {
                console.error("Failed to fetch profile:", error);
            } finally {
                loading.value = false;
            }
        };

        const saveProfile = async () => {
            if (!valid.value) return;
            try {
                saving.value = true;
                await axios.put("/api/profile/", profile.value, {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                profile.value.current_password = "";
                profile.value.new_password = "";
                profile.value.new_password_confirmation = "";
            } catch (error) {
                console.error("Failed to save profile:", error);
            } finally {
                saving.value = false;
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

        fetchProfile();

        return {
            menuItems,
            loading,
            saving,
            valid,
            form,
            profile,
            saveProfile,
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
    background-color: rgba(46, 46, 46, 0.9);
}
</style>
