<template>
    <default-layout title="Profile" :menu-items="menuItems" @logout="logout">
        <v-row>
            <v-col cols="12" md="6" offset-md="3">
                <v-card>
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
    },
    data() {
        return {
            menuItems,
            loading: false,
            saving: false,
            valid: false,
            profile: {
                name: "",
                email: "",
                username: "",
                phone: "",
                dob: "",
                address: "",
                current_password: "",
                new_password: "",
                new_password_confirmation: "",
            } as Profile,
        };
    },
    methods: {
        async fetchProfile() {
            try {
                this.loading = true;
                const response = await axios.get("/api/profile/me", {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                    },
                });
                console.log(response.data); // Add this line
                // Access properties through the "user" key
                this.profile.name = response.data.user.name;
                this.profile.email = response.data.user.email;
                this.profile.username = response.data.user.username;
                this.profile.phone = response.data.user.phone;
                this.profile.dob = response.data.user.dob;
                this.profile.address = response.data.user.address;
            } catch (error) {
                console.error("Failed to fetch profile:", error);
            } finally {
                this.loading = false;
            }
        },
        async saveProfile() {
            if (!this.valid) return;

            try {
                this.saving = true;
                await axios.put("/api/profile/", this.profile, {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                    },
                });
                // Clear password fields after successful save
                this.profile.current_password = "";
                this.profile.new_password = "";
                this.profile.new_password_confirmation = "";
            } catch (error) {
                console.error("Failed to save profile:", error);
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
        this.fetchProfile();
    },
});
</script>
