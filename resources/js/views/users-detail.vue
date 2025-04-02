<template>
    <default-layout
        title="User Detail"
        :menu-items="menuItems"
        @logout="logout"
    >
        <v-row>
            <v-col cols="12" md="6" offset-md="3">
                <v-card>
                    <v-card-title>User Detail Settings</v-card-title>
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
                            @submit.prevent="saveUsersDetail"
                        >
                            <v-text-field
                                v-model="UsersDetail.name"
                                label="Name"
                                :rules="[(v) => !!v || 'Name is required']"
                                required
                            ></v-text-field>

                            <v-text-field
                                v-model="UsersDetail.email"
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
                                v-model="UsersDetail.current_password"
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

                            <v-text-field
                                v-model="UsersDetail.new_password_confirmation"
                                label="Confirm New Password"
                                type="password"
                                :rules="[
                                    (v) =>
                                        !v ||
                                        v === UsersDetail.new_password ||
                                        'Passwords must match',
                                ]"
                            ></v-text-field>

                            <v-btn
                                color="primary"
                                type="submit"
                                :loading="saving"
                                :disabled="!valid"
                            >
                                Save Changes
                            </v-btn>
                        </v-form>
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
                current_password: "",
                new_password: "",
                new_password_confirmation: "",
            } as UsersDetail,
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
                this.UsersDetail.name = response.data.name;
                this.UsersDetail.email = response.data.email;
            } catch (error) {
                console.error("Failed to fetch UsersDetail:", error);
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
    },
    mounted() {
        // Get the user ID from the route parameters
        this.userId = parseInt(this.$route.params.id as string);
        if (this.userId) {
            this.fetchUsersDetail();
        } else {
            console.error("User ID not found in route parameters.");
        }
    },
});
</script>
