<template>
    <DefaultLayout :menu-items="menuItems" title="Users">
        <v-container>
            <!-- Search and Add User Button -->
            <v-row>
                <v-col cols="12" sm="6" md="4">
                    <v-text-field
                        v-model="search"
                        prepend-inner-icon="mdi-magnify"
                        label="Search by name"
                        clearable
                        outlined
                        dense
                        @input="handleSearch"
                    ></v-text-field>
                </v-col>
                <v-col cols="12" sm="6" md="8" class="text-right">
                    <v-btn color="primary" @click="openAddDialog">
                        <v-icon left>mdi-plus</v-icon>
                        Add User
                    </v-btn>
                </v-col>
            </v-row>

            <!-- Users Table -->
            <v-data-table-server
                :headers="headers"
                :items="users"
                :items-length="totalUsers"
                :loading="loading"
                v-model:options="options"
                :items-per-page="options.itemsPerPage"
                :page="options.page"
                :footer-props="{
                    'items-per-page-options': [10, 25, 50],
                    showFirstLastPage: true,
                    'items-per-page-text': 'Rows per page',
                }"
                @update:options="handleTableUpdate"
                class="elevation-1"
                must-sort
                item-key="id"
            >
                <!-- Created At Column -->
                <template v-slot:item.created_at="{ item }">
                    {{ formatDate(item.created_at) }}
                </template>

                <!-- Is Admin Column -->
                <template v-slot:item.is_admin="{ item }">
                    <v-chip
                        :color="item.is_admin ? 'success' : 'default'"
                        small
                    >
                        {{ item.is_admin ? "Yes" : "No" }}
                    </v-chip>
                </template>

                <!-- Actions Column -->
                <template v-slot:item.actions="{ item }">
                    <v-icon
                        small
                        class="mr-2"
                        @click.stop="viewUser(item)"
                        color="primary"
                    >
                        mdi-pencil
                    </v-icon>
                    <v-icon
                        small
                        @click.stop="deleteUser(item)"
                        color="primary"
                    >
                        mdi-delete
                    </v-icon>
                </template>
            </v-data-table-server>

            <!-- Add/Edit Dialog -->
            <v-dialog v-model="dialog" max-width="500px">
                <v-card>
                    <v-card-title>
                        <span>{{ formTitle }}</span>
                    </v-card-title>

                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editedItem.name"
                                        label="Name"
                                        :error-messages="validationErrors.name"
                                        :rules="[
                                            (v) => !!v || 'Name is required',
                                        ]"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editedItem.email"
                                        label="Email"
                                        type="email"
                                        :error-messages="validationErrors.email"
                                        :rules="[
                                            (v) => !!v || 'Email is required',
                                            (v) =>
                                                /.+@.+\..+/.test(v) ||
                                                'Email must be valid',
                                        ]"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editedItem.username"
                                        label="Username"
                                        :error-messages="
                                            validationErrors.username
                                        "
                                        :rules="[
                                            (v) =>
                                                !!v || 'Username is required',
                                        ]"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" v-if="editedIndex === -1">
                                    <v-text-field
                                        v-model="editedItem.password"
                                        label="Password"
                                        type="password"
                                        :error-messages="
                                            validationErrors.password
                                        "
                                        :rules="[
                                            (v) =>
                                                !!v || 'Password is required',
                                        ]"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" v-if="editedIndex === -1">
                                    <v-text-field
                                        v-model="
                                            editedItem.password_confirmation
                                        "
                                        label="Password Confirmation"
                                        type="password"
                                        :error-messages="
                                            validationErrors.password_confirmation
                                        "
                                        :rules="[
                                            (v) =>
                                                !!v ||
                                                'Password Confirmation is required',
                                            (v) =>
                                                v === editedItem.password ||
                                                'Password confirmation does not match',
                                        ]"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-switch
                                        v-model="editedItem.is_admin"
                                        label="Admin Access"
                                    ></v-switch>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>

                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="grey darken-1" text @click="close">
                            Cancel
                        </v-btn>
                        <v-btn color="primary" text @click="save">Save</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Delete Confirmation Dialog -->
            <v-dialog v-model="deleteDialog" max-width="400px">
                <v-card>
                    <v-card-title>Delete User</v-card-title>
                    <v-card-text>
                        Are you sure you want to delete this user?
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="grey darken-1"
                            text
                            @click="deleteDialog = false"
                        >
                            Cancel
                        </v-btn>
                        <v-btn color="error" text @click="confirmDelete">
                            Delete
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
        <v-snackbar
            v-model="snackbar"
            :color="snackbarColor"
            :timeout="3000"
            :top="true"
            :right="true"
        >
            {{ snackbarText }}
            <template v-slot:actions>
                <v-btn color="white" text @click="snackbar = false">
                    Close
                </v-btn>
            </template>
        </v-snackbar>
    </DefaultLayout>
</template>

<script>
import axios from "axios";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import { menuItems } from "@/config/menu.ts";

export default {
    name: "UsersTable",
    components: {
        DefaultLayout,
    },
    data: () => ({
        menuItems,
        search: "",
        loading: false,
        dialog: false,
        deleteDialog: false,
        options: {
            page: 1,
            itemsPerPage: 10,
            sortBy: [],
            sortDesc: [],
            groupBy: [],
            groupDesc: [],
            multiSort: false,
        },
        totalUsers: 0,
        headers: [
            { title: "Name", key: "name", align: "start", sortable: true },
            { title: "Email", key: "email", align: "start", sortable: true },
            {
                title: "Username",
                key: "username",
                align: "start",
                sortable: true,
            },
            {
                title: "Created At",
                key: "created_at",
                align: "start",
                sortable: true,
            },
            { title: "Admin", key: "is_admin", align: "start", sortable: true },
            {
                title: "Actions",
                key: "actions",
                align: "center",
                sortable: false,
            },
        ],
        users: [],
        editedIndex: -1,
        editedItem: {
            name: "",
            email: "",
            username: "",
            password: "",
            password_confirmation: "",
            is_admin: false,
        },
        defaultItem: {
            name: "",
            email: "",
            username: "",
            password: "",
            password_confirmation: "",
            is_admin: false,
        },
        userToDelete: null,
        snackbar: false,
        snackbarText: "",
        snackbarColor: "success",
        validationErrors: {},
    }),

    computed: {
        formTitle() {
            return this.editedIndex === -1 ? "New User" : "Edit User";
        },
    },

    methods: {
        showSnackbar(text, color = "success") {
            this.snackbarText = text;
            this.snackbarColor = color;
            this.snackbar = true;
        },
        handleTableUpdate(newOptions) {
            this.options = {
                ...this.options,
                page: newOptions.page || 1,
                itemsPerPage: newOptions.itemsPerPage || 10,
                sortBy: Array.isArray(newOptions.sortBy)
                    ? newOptions.sortBy
                    : this.options.sortBy || [],
                sortDesc: Array.isArray(newOptions.sortDesc)
                    ? newOptions.sortDesc
                    : this.options.sortDesc || [],
            };
            this.fetchUsers();
        },
        async fetchUsers() {
            if (this.loading) return;

            this.loading = true;
            try {
                const { page, itemsPerPage, sortBy = [] } = this.options;

                console.log("fetchUsers options:", { sortBy });

                const sort_by =
                    sortBy.length > 0 ? sortBy[0].key : "created_at";
                const sort_desc =
                    sortBy.length > 0 ? sortBy[0].order === "desc" : false;

                const response = await axios.get("/api/users", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                    },
                    params: {
                        page,
                        per_page: itemsPerPage,
                        search: this.search,
                        sort_by,
                        sort_desc: sort_desc ? 1 : 0,
                    },
                });

                this.users = response.data.data;
                this.totalUsers = response.data.meta.total;
            } catch (error) {
                this.showSnackbar("Error loading users", "error");
                console.error("Error fetching users:", error);
            } finally {
                this.loading = false;
            }
        },
        handleSearch: debounce(function () {
            this.options.page = 1; // Reset to first page when searching
            this.fetchUsers();
        }, 300),

        formatDate(date) {
            return new Date(date).toLocaleDateString();
        },

        openAddDialog() {
            this.editedIndex = -1;
            this.editedItem = { ...this.defaultItem };
            this.dialog = true;
        },

        deleteUser(item) {
            this.userToDelete = item;
            this.deleteDialog = true;
        },

        // Update your confirmDelete method
        async confirmDelete() {
            try {
                await axios.delete(`/api/users/${this.userToDelete.id}`, {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                    },
                });
                this.showSnackbar("User deleted successfully");
                await this.fetchUsers();
                this.deleteDialog = false;
                this.userToDelete = null;
            } catch (error) {
                this.showSnackbar("Error deleting user", "error");
                console.error("Error deleting user:", error);
            }
        },

        close() {
            this.dialog = false;
            this.validationErrors = {};
            this.$nextTick(() => {
                this.editedItem = { ...this.defaultItem };
                this.editedIndex = -1;
            });
        },
        // Update your existing save method
        async save() {
            try {
                this.validationErrors = {};
                const headers = {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                    "Content-Type": "application/json",
                };

                const data = {
                    name: this.editedItem.name,
                    email: this.editedItem.email,
                    username: this.editedItem.username,
                    is_admin: this.editedItem.is_admin,
                };

                if (this.editedIndex === -1) {
                    data.password = this.editedItem.password;
                    data.password_confirmation =
                        this.editedItem.password_confirmation;
                }

                if (this.editedIndex > -1) {
                    await axios.put(`/api/users/${this.editedItem.id}`, data, {
                        headers,
                    });
                    this.showSnackbar("User updated successfully");
                } else {
                    await axios.post("/api/users", data, {
                        headers,
                    });
                    this.showSnackbar("User created successfully");
                }

                await this.fetchUsers();
                this.close();
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.validationErrors = error.response.data.errors || {};
                    this.showSnackbar(
                        "Please check the form for errors",
                        "error"
                    );
                } else {
                    this.showSnackbar(
                        "An error occurred while saving",
                        "error"
                    );
                    console.error("Error saving user:", error);
                }
            }
        },
        viewUser(item) {
            this.$router.push(`/users/${item.id}`);
        },
    },

    mounted() {
        this.fetchUsers();
    },
};

// Debounce function to prevent too many API calls while searching
function debounce(fn, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}
</script>
