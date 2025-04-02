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
                :sort-by="options.sortBy"
                :sort-desc="options.sortDesc"
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
                    <v-btn
                        color="primary"
                        class="mr-2"
                        @click.stop="viewUser(item)"
                    >
                        <v-icon left>mdi-eye</v-icon>
                        View
                    </v-btn>
                    <v-icon small class="mr-2" @click.stop="editUser(item)">
                        mdi-pencil
                    </v-icon>
                    <v-icon small @click.stop="deleteUser(item)">
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
        lastSortBy: null,
        lastSortDesc: 1,
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
            {
                title: "Name",
                key: "name",
                align: "start",
                sortable: true,
            },
            {
                title: "Email",
                key: "email",
                align: "start",
                sortable: true,
            },
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
            {
                title: "Admin",
                key: "is_admin",
                align: "start",
                sortable: true,
            },
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
            is_admin: false,
        },
        defaultItem: {
            name: "",
            email: "",
            username: "",
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
        handleTableUpdate(newOptions) {
            // Ensure we have valid options with safe defaults
            this.options = {
                ...this.options,
                page: newOptions.page || 1,
                itemsPerPage: newOptions.itemsPerPage || 10,
                sortBy: [],
                sortDesc: [],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
            };

            // Handle sorting separately
            if (newOptions.sortBy && newOptions.sortBy.length > 0) {
                this.options.sortBy = newOptions.sortBy;
                this.options.sortDesc = newOptions.sortDesc;
            }

            this.fetchUsers();
        },
        showSnackbar(text, color = "success") {
            this.snackbarText = text;
            this.snackbarColor = color;
            this.snackbar = true;
        },

        async fetchUsers() {
            if (this.loading) return;

            this.loading = true;
            try {
                const {
                    page = 1,
                    itemsPerPage = 10,
                    sortBy = [],
                    sortDesc = [],
                } = this.options;

                // Extract sort parameters
                let sort_by = "created_at";
                let sort_desc = 1; // default to descending

                if (sortBy.length > 0) {
                    // Extract just the column name if sortBy contains an object
                    sort_by =
                        typeof sortBy[0] === "object"
                            ? sortBy[0].key
                            : sortBy[0];

                    // Check if this is a new sort or toggling existing sort
                    if (this.lastSortBy !== sort_by) {
                        // New column sort - start with descending (1)
                        sort_desc = 1;
                        this.lastSortBy = sort_by;
                    } else {
                        // Toggle sort direction
                        sort_desc = this.lastSortDesc === 1 ? 0 : 1;
                    }

                    // Store the current sort direction
                    this.lastSortDesc = sort_desc;
                }

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
                        sort_desc,
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

        editUser(item) {
            this.editedIndex = this.users.indexOf(item);
            this.editedItem = { ...item };
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

                if (this.editedIndex > -1) {
                    await axios.put(
                        `/api/users/${this.editedItem.id}`,
                        this.editedItem,
                        { headers }
                    );
                    this.showSnackbar("User updated successfully");
                } else {
                    await axios.post("/api/users", this.editedItem, {
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
