<template>
    <default-layout
        title="User Detail"
        :menu-items="menuItems"
        @logout="logout"
    >
        <v-row>
            <v-col cols="12" md="10" offset-md="1">
                <v-card>
                    <v-card-title>User Detail Settings</v-card-title>
                    <v-card-text>
                        <v-progress-linear
                            v-if="loading"
                            indeterminate
                            color="primary"
                        ></v-progress-linear>

                        <div v-else>
                            <!-- Main User Details -->
                            <v-form ref="form" v-model="valid">
                                <v-row>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="UsersDetail.name"
                                            label="Name"
                                            :rules="[
                                                (v) =>
                                                    !!v || 'Name is required',
                                            ]"
                                            required
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="UsersDetail.email"
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
                                            v-model="UsersDetail.username"
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
                                            v-model="UsersDetail.phone"
                                            label="Phone"
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="UsersDetail.dob"
                                            label="Date of Birth"
                                            type="date"
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="UsersDetail.address"
                                            label="Address"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="
                                                UsersDetail.current_password
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
                                            v-model="UsersDetail.new_password"
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
                                                UsersDetail.new_password_confirmation
                                            "
                                            label="Confirm New Password"
                                            type="password"
                                            :rules="[
                                                (v) =>
                                                    !v ||
                                                    v ===
                                                        UsersDetail.new_password ||
                                                    'Passwords must match',
                                            ]"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col cols="12" md="4">
                                        <v-btn
                                            :to="{
                                                name: 'FaceModelList',
                                                params: { id: userId },
                                            }"
                                        >
                                            Manage Face Models
                                        </v-btn>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-btn
                                            color="primary"
                                            type="submit"
                                            :loading="saving"
                                            :disabled="!valid"
                                            @click="saveUsersDetail"
                                        >
                                            Save Changes
                                        </v-btn>
                                    </v-col>
                                </v-row>
                            </v-form>

                            <!-- Additional Data Cards -->
                            <v-row class="mt-4">
                                <v-col
                                    v-for="(data, key) in additionalData"
                                    :key="key"
                                    cols="12"
                                    md="4"
                                >
                                    <v-card>
                                        <v-card-title>
                                            {{ formatKey(key) }}
                                        </v-card-title>
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
                                                    <v-list-item-title>
                                                        {{
                                                            formatSubKey(subKey)
                                                        }}
                                                    </v-list-item-title>
                                                    <v-list-item-subtitle>
                                                        {{ value }}
                                                    </v-list-item-subtitle>
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
import { defineComponent } from "vue";
import axios from "axios";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import { menuItems } from "../config/menu";

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
    name: "Users Detail",
    components: {
        DefaultLayout,
    },
    data() {
        return {
            menuItems,
            loading: false,
            saving: false,
            valid: false,
            UsersDetail: {
                name: "",
                email: "",
                username: "",
                phone: "",
                dob: "",
                address: "",
                current_password: "",
                new_password: "",
                new_password_confirmation: "",
            } as UsersDetail,
            additionalData: {},
            userId: null as number | null,
        };
    },
    methods: {
        async fetchUsersDetail() {
            try {
                this.loading = true;
                const response = await axios.get(`/api/users/${this.userId}`, {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                    },
                });
                console.log(response.data);
                // Populate main user details
                this.UsersDetail.name = response.data.user.name;
                this.UsersDetail.email = response.data.user.email;
                this.UsersDetail.username = response.data.user.username;
                this.UsersDetail.phone = response.data.user.phone;
                this.UsersDetail.dob = response.data.user.dob;
                this.UsersDetail.address = response.data.user.address;

                // Populate additional data
                this.additionalData = {
                    dataPegawaiSimpeg: response.data.dataPegawaiSimpeg,
                    dataPegawaiAbsen: response.data.dataPegawaiAbsen,
                    dataPegawaiEkinerja: response.data.dataPegawaiEkinerja,
                    userAbsen: response.data.userAbsen,
                    userEkinerja: response.data.userEkinerja,
                };
            } catch (error) {
                console.error("Failed to fetch User Detail:", error);
            } finally {
                this.loading = false;
            }
        },
        async saveUsersDetail() {
            if (!this.valid) return;

            try {
                this.saving = true;
                await axios.put(`/api/users/${this.userId}`, this.UsersDetail, {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                    },
                });
                // Clear password fields after successful save
                this.UsersDetail.current_password = "";
                this.UsersDetail.new_password = "";
                this.UsersDetail.new_password_confirmation = "";
            } catch (error) {
                console.error("Failed to save Users Detail:", error);
            } finally {
                this.saving = false;
            }
        },
        logout() {
            localStorage.removeItem("token");
            this.$router.push({ name: "Login" });
        },
        formatKey(key: string): string {
            return key
                .replace(/([A-Z])/g, " $1")
                .replace(/^./, (str) => str.toUpperCase());
        },
        formatSubKey(key: string | number): string {
            if (typeof key === "number") {
                return key.toString();
            }
            return key
                .replace(/([A-Z])/g, " $1")
                .replace(/^./, (str) => str.toUpperCase());
        },
        isObject(obj: any): boolean {
            return typeof obj === "object" && obj !== null;
        },
    },
    mounted() {
        // Get the user ID from the route parameters
        this.userId = parseInt(this.$route.params.id as string, 10);
        if (this.userId) {
            this.fetchUsersDetail();
        } else {
            console.error("User ID not found in route parameters.");
        }
    },
});
</script>
