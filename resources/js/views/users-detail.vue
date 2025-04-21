<template>
    <default-layout
        title="User Detail"
        :menu-items="menuItems"
        @logout="handleLogout"
    >
        <v-row>
            <v-col cols="12" md="10" offset-md="1">
                <v-card class="dashboard-card" elevation="3">
                    <v-card-title>User Detail Settings</v-card-title>
                    <v-card-text>
                        <v-progress-linear
                            v-if="loading"
                            indeterminate
                            color="primary"
                        ></v-progress-linear>
                        <div v-else>
                            <v-form ref="form" v-model="valid">
                                <v-row>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="usersDetail.name"
                                            label="Name"
                                            :rules="[
                                                (v) =>
                                                    !!v || 'Name is required',
                                            ]"
                                            required
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="usersDetail.email"
                                            label="Email"
                                            :rules="[
                                                (v) =>
                                                    !!v || 'Email is required',
                                                (v) =>
                                                    /.+@.+\..+/.test(v) ||
                                                    'Email must be valid',
                                            ]"
                                            required
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="usersDetail.username"
                                            label="Username"
                                            :rules="[
                                                (v) =>
                                                    !!v ||
                                                    'Username is required',
                                            ]"
                                            required
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="usersDetail.phone"
                                            label="Phone"
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="usersDetail.dob"
                                            label="Date of Birth"
                                            type="date"
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="usersDetail.address"
                                            label="Address"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="
                                                usersDetail.current_password
                                            "
                                            label="Current Password"
                                            type="password"
                                            :rules="[
                                                (v) =>
                                                    !v ||
                                                    v.length >= 8 ||
                                                    'Password must be at least 8 characters',
                                            ]"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="usersDetail.new_password"
                                            label="New Password"
                                            type="password"
                                            :rules="[
                                                (v) =>
                                                    !v ||
                                                    v.length >= 8 ||
                                                    'Password must be at least 8 characters',
                                            ]"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="
                                                usersDetail.new_password_confirmation
                                            "
                                            label="Confirm New Password"
                                            type="password"
                                            :rules="[
                                                (v) =>
                                                    !v ||
                                                    v ===
                                                        usersDetail.new_password ||
                                                    'Passwords must match',
                                            ]"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col cols="12" md="4">
                                        <BaseButton
                                            color="primary"
                                            :to="{
                                                name: 'faceModelList',
                                                params: { id: userId },
                                            }"
                                        >
                                            Manage Face Models
                                        </BaseButton>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <BaseButton
                                            color="primary"
                                            :loading="saving"
                                            :disabled="!valid"
                                            @click="saveUsersDetail"
                                        >
                                            Save Changes
                                        </BaseButton>
                                    </v-col>
                                </v-row>
                            </v-form>
                            <v-row class="mt-4">
                                <v-col
                                    v-for="(data, key) in additionalData"
                                    :key="key"
                                    cols="12"
                                    md="4"
                                >
                                    <v-card
                                        class="dashboard-card"
                                        elevation="3"
                                    >
                                        <v-card-title>{{
                                            formatKey(key)
                                        }}</v-card-title>
                                        <v-card-text>
                                            <v-list>
                                                <v-list-item
                                                    v-for="(
                                                        value, subKey
                                                    ) in isObject(data)
                                                        ? data
                                                        : []"
                                                    :key="subKey"
                                                >
                                                    <v-list-item-title>{{
                                                        formatSubKey(subKey)
                                                    }}</v-list-item-title>
                                                    <v-list-item-subtitle>{{
                                                        value
                                                    }}</v-list-item-subtitle>
                                                </v-list-item>
                                            </v-list>
                                        </v-card-text>
                                    </v-card>
                                </v-col>
                            </v-row>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </default-layout>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from "vue";
import axios from "axios";
import { useRoute, useRouter } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import BaseButton from "@/components/BaseButton.vue";
import { useAuth } from "@/composables/useAuth";
import { adminMenuItems, userMenuItems } from "@/config/menu";

interface UsersDetail {
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
    name: "UsersDetail",
    components: {
        DefaultLayout,
        BaseButton,
    },
    setup() {
        const route = useRoute();
        const router = useRouter();
        const { logout, isAdmin } = useAuth();
        const loading = ref(false);
        const saving = ref(false);
        const valid = ref(false);
        const form = ref(null);
        const usersDetail = ref<UsersDetail>({
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
        const additionalData = ref({});
        const userId = ref<number | null>(null);
        const menuItems = ref(isAdmin.value ? adminMenuItems : userMenuItems);

        watch(isAdmin, (newValue) => {
            menuItems.value = newValue ? adminMenuItems : userMenuItems;
        });

        const fetchUsersDetail = async () => {
            try {
                loading.value = true;
                const response = await axios.get(`/api/users/${userId.value}`, {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                usersDetail.value.name = response.data.user.name;
                usersDetail.value.email = response.data.user.email;
                usersDetail.value.username = response.data.user.username;
                usersDetail.value.phone = response.data.user.phone;
                usersDetail.value.dob = response.data.user.dob;
                usersDetail.value.address = response.data.user.address;
                additionalData.value = {
                    dataPegawaiSimpeg: response.data.dataPegawaiSimpeg,
                    dataPegawaiAbsen: response.data.dataPegawaiAbsen,
                    dataPegawaiEkinerja: response.data.dataPegawaiEkinerja,
                    userAbsen: response.data.userAbsen,
                    userEkinerja: response.data.userEkinerja,
                };
            } catch (error) {
                console.error("Failed to fetch User Detail:", error);
            } finally {
                loading.value = false;
            }
        };

        const saveUsersDetail = async () => {
            if (!valid.value) return;
            try {
                saving.value = true;
                await axios.put(
                    `/api/users/${userId.value}`,
                    usersDetail.value,
                    {
                        headers: {
                            Accept: "application/json",
                            "Content-Type": "application/json",
                            Authorization: `Bearer ${localStorage.getItem(
                                "auth_token"
                            )}`,
                        },
                    }
                );
                usersDetail.value.current_password = "";
                usersDetail.value.new_password = "";
                usersDetail.value.new_password_confirmation = "";
            } catch (error) {
                console.error("Failed to save Users Detail:", error);
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

        const formatKey = (key: string): string => {
            return key
                .replace(/([A-Z])/g, " $1")
                .replace(/^./, (str) => str.toUpperCase());
        };

        const formatSubKey = (key: string | number): string => {
            if (typeof key === "number") {
                return key.toString();
            }
            return key
                .replace(/([A-Z])/g, " $1")
                .replace(/^./, (str) => str.toUpperCase());
        };

        const isObject = (obj: any): boolean => {
            return typeof obj === "object" && obj !== null;
        };

        userId.value = parseInt(route.params.id as string, 10);
        if (userId.value) {
            fetchUsersDetail();
        } else {
            console.error("User ID not found in route parameters.");
        }

        return {
            menuItems,
            loading,
            saving,
            valid,
            form,
            usersDetail,
            additionalData,
            userId,
            saveUsersDetail,
            handleLogout,
            formatKey,
            formatSubKey,
            isObject,
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
