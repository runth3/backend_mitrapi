<template>
    <default-layout
        title="Users"
        :menu-items="menuItems"
        @logout="handleLogout"
    >
        <v-container>
            <v-row>
                <v-col cols="12" sm="6" md="4">
                    <v-text-field
                        v-model="search"
                        prepend-inner-icon="mdi-magnify"
                        label="Search by name"
                        variant="outlined"
                        density="compact"
                        clearable
                        @input="handleSearch"
                    ></v-text-field>
                </v-col>
                <v-col cols="12" sm="6" md="8" class="text-right">
                    <BaseButton color="primary" @click="openAddDialog">
                        <v-icon left>mdi-plus</v-icon>
                        Add User
                    </BaseButton>
                </v-col>
            </v-row>
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
                <template #item.created_at="{ item }">
                    {{ formatDate(item.created_at) }}
                </template>
                <template #item.is_admin="{ item }">
                    <v-chip
                        :color="item.is_admin ? 'success' : 'default'"
                        small
                    >
                        {{ item.is_admin ? "Yes" : "No" }}
                    </v-chip>
                </template>
                <template #item.actions="{ item }">
                    <v-icon
                        small
                        class="mr-2"
                        @click.stop="viewUser(item)"
                        color="primary"
                    >
                        mdi-pencil
                    </v-icon>
                    <v-icon small @click.stop="deleteUser(item)" color="error">
                        mdi-delete
                    </v-icon>
                </template>
            </v-data-table-server>
            <v-dialog v-model="dialog" max-width="500px">
                <v-card class="dashboard-card" elevation="3">
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
                        <BaseButton
                            color="secondary"
                            variant="text"
                            @click="close"
                        >
                            Cancel
                        </BaseButton>
                        <BaseButton
                            color="primary"
                            variant="text"
                            @click="save"
                        >
                            Save
                        </BaseButton>
                    </v-card-actions>
                </v-card>
            </v-dialog>
            <v-dialog v-model="deleteDialog" max-width="400px">
                <v-card class="dashboard-card" elevation="3">
                    <v-card-title>Delete User</v-card-title>
                    <v-card-text>
                        Are you sure you want to delete this user?
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <BaseButton
                            color="secondary"
                            variant="text"
                            @click="deleteDialog = false"
                        >
                            Cancel
                        </BaseButton>
                        <BaseButton
                            color="error"
                            variant="text"
                            @click="confirmDelete"
                        >
                            Delete
                        </BaseButton>
                    </v-card-actions>
                </v-card>
            </v-dialog>
            <v-snackbar
                v-model="snackbar"
                :color="snackbarColor"
                :timeout="3000"
                location="top"
                vertical
            >
                {{ snackbarText }}
                <template #actions>
                    <BaseButton
                        color="white"
                        variant="text"
                        @click="snackbar = false"
                    >
                        Close
                    </BaseButton>
                </template>
            </v-snackbar>
        </v-container>
    </default-layout>
</template>

<script lang="ts">
import { defineComponent, ref, watch, computed } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import BaseButton from "@/components/BaseButton.vue";
import { useAuth } from "@/composables/useAuth";
import { adminMenuItems, userMenuItems } from "@/config/menu";

interface User {
    id: number;
    name: string;
    email: string;
    username: string;
    created_at: string;
    is_admin: boolean;
}

export default defineComponent({
    name: "UsersTable",
    components: {
        DefaultLayout,
        BaseButton,
    },
    setup() {
        const router = useRouter();
        const { logout, isAdmin } = useAuth();
        const search = ref("");
        const loading = ref(false);
        const dialog = ref(false);
        const deleteDialog = ref(false);
        const options = ref({
            page: 1,
            itemsPerPage: 10,
            sortBy: [],
            sortDesc: [],
            groupBy: [],
            groupDesc: [],
            multiSort: false,
        });
        const totalUsers = ref(0);
        const headers = ref([
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
        ]);
        const users = ref<User[]>([]);
        const editedIndex = ref(-1);
        const editedItem = ref({
            name: "",
            email: "",
            username: "",
            password: "",
            password_confirmation: "",
            is_admin: false,
        });
        const defaultItem = ref({
            name: "",
            email: "",
            username: "",
            password: "",
            password_confirmation: "",
            is_admin: false,
        });
        const userToDelete = ref<User | null>(null);
        const snackbar = ref(false);
        const snackbarText = ref("");
        const snackbarColor = ref<"success" | "error">("success");
        const validationErrors = ref({});
        const menuItems = ref(isAdmin.value ? adminMenuItems : userMenuItems);

        watch(isAdmin, (newValue) => {
            menuItems.value = newValue ? adminMenuItems : userMenuItems;
        });

        const formTitle = computed(() => {
            return editedIndex.value === -1 ? "New User" : "Edit User";
        });

        const showSnackbar = (
            text: string,
            color: "success" | "error" = "success"
        ) => {
            snackbarText.value = text;
            snackbarColor.value = color;
            snackbar.value = true;
        };

        const handleTableUpdate = (newOptions: any) => {
            options.value = {
                ...options.value,
                page: newOptions.page || 1,
                itemsPerPage: newOptions.itemsPerPage || 10,
                sortBy: Array.isArray(newOptions.sortBy)
                    ? newOptions.sortBy
                    : options.value.sortBy || [],
                sortDesc: Array.isArray(newOptions.sortDesc)
                    ? newOptions.sortDesc
                    : options.value.sortDesc || [],
            };
            fetchUsers();
        };

        const fetchUsers = async () => {
            if (loading.value) return;
            loading.value = true;
            try {
                const { page, itemsPerPage, sortBy = [] } = options.value;
                const sort_by =
                    sortBy.length > 0 ? sortBy[0].key : "created_at";
                const sort_desc =
                    sortBy.length > 0 ? sortBy[0].order === "desc" : false;
                const response = await axios.get("/api/users", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                    params: {
                        page,
                        per_page: itemsPerPage,
                        search: search.value,
                        sort_by,
                        sort_desc: sort_desc ? 1 : 0,
                    },
                });
                users.value = response.data.data;
                totalUsers.value = response.data.meta.total;
            } catch (error) {
                showSnackbar("Error loading users", "error");
                console.error("Error fetching users:", error);
            } finally {
                loading.value = false;
            }
        };

        const handleSearch = debounce(() => {
            options.value.page = 1;
            fetchUsers();
        }, 300);

        const formatDate = (date: string) => {
            return new Date(date).toLocaleDateString();
        };

        const openAddDialog = () => {
            editedIndex.value = -1;
            editedItem.value = { ...defaultItem.value };
            dialog.value = true;
        };

        const deleteUser = (item: User) => {
            userToDelete.value = item;
            deleteDialog.value = true;
        };

        const confirmDelete = async () => {
            try {
                await axios.delete(`/api/users/${userToDelete.value!.id}`, {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                showSnackbar("User deleted successfully");
                await fetchUsers();
                deleteDialog.value = false;
                userToDelete.value = null;
            } catch (error) {
                showSnackbar("Error deleting user", "error");
                console.error("Error deleting user:", error);
            }
        };

        const close = () => {
            dialog.value = false;
            validationErrors.value = {};
            editedItem.value = { ...defaultItem.value };
            editedIndex.value = -1;
        };

        const save = async () => {
            try {
                validationErrors.value = {};
                const headers = {
                    Authorization: `Bearer ${localStorage.getItem(
                        "auth_token"
                    )}`,
                    "Content-Type": "application/json",
                };
                const data = {
                    name: editedItem.value.name,
                    email: editedItem.value.email,
                    username: editedItem.value.username,
                    is_admin: editedItem.value.is_admin,
                };
                if (editedIndex.value === -1) {
                    data.password = editedItem.value.password;
                    data.password_confirmation =
                        editedItem.value.password_confirmation;
                }
                if (editedIndex.value > -1) {
                    await axios.put(`/api/users/${editedItem.value.id}`, data, {
                        headers,
                    });
                    showSnackbar("User updated successfully");
                } else {
                    await axios.post("/api/users", data, { headers });
                    showSnackbar("User created successfully");
                }
                await fetchUsers();
                close();
            } catch (error: any) {
                if (error.response && error.response.status === 422) {
                    validationErrors.value = error.response.data.errors || {};
                    showSnackbar("Please check the form for errors", "error");
                } else {
                    showSnackbar("An error occurred while saving", "error");
                    console.error("Error saving user:", error);
                }
            }
        };

        const viewUser = (item: User) => {
            router.push(`/users/${item.id}`);
        };

        const handleLogout = async () => {
            try {
                await logout();
                router.push({ name: "Login" });
            } catch (err) {
                console.error("Logout failed:", err);
            }
        };

        fetchUsers();

        return {
            menuItems,
            search,
            loading,
            dialog,
            deleteDialog,
            options,
            totalUsers,
            headers,
            users,
            editedIndex,
            editedItem,
            defaultItem,
            userToDelete,
            snackbar,
            snackbarText,
            snackbarColor,
            validationErrors,
            formTitle,
            showSnackbar,
            handleTableUpdate,
            handleSearch,
            formatDate,
            openAddDialog,
            deleteUser,
            confirmDelete,
            close,
            save,
            viewUser,
            handleLogout,
        };
    },
});

function debounce(fn: (...args: any[]) => void, delay: number) {
    let timeoutId: NodeJS.Timeout;
    return (...args: any[]) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
}
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
