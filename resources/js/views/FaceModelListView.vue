<template>
    <default-layout
        title="Face Model List"
        :menu-items="menuItems"
        @logout="handleLogout"
    >
        <v-container v-if="userId">
            <v-card class="dashboard-card" elevation="3">
                <v-card-title v-if="userDetails">
                    Face Models: {{ userDetails.username }} /
                    {{ userDetails.name }}
                </v-card-title>
                <v-card-text>
                    <!-- Upload Section -->
                    <v-row class="mt-4">
                        <v-col cols="12">
                            <v-file-input
                                ref="fileInput"
                                label="Upload New Face Model"
                                accept="image/png, image/jpeg, image/jpg"
                                prepend-icon="mdi-camera"
                                class="mt-4"
                                @change="handleImageChange"
                            ></v-file-input>
                            <BaseButton
                                color="primary"
                                class="mt-2"
                                :loading="uploading"
                                :disabled="!imageFile"
                                @click="handleUpload"
                            >
                                Upload
                            </BaseButton>
                        </v-col>
                    </v-row>

                    <!-- List Section with Cards -->
                    <v-progress-linear
                        v-if="loading"
                        indeterminate
                        color="primary"
                    ></v-progress-linear>
                    <v-row v-else-if="faceModels.length > 0" class="mt-6">
                        <v-col
                            v-for="model in faceModels"
                            :key="model.id"
                            cols="12"
                            sm="6"
                            md="3"
                        >
                            <v-card
                                elevation="4"
                                class="pa-4 image-card"
                                :class="{ 'active-card': model.is_active }"
                                :style="{
                                    backgroundImage: `url(${
                                        imageBlobs[model.id] || ''
                                    })`,
                                }"
                            >
                                <v-row
                                    align="center"
                                    justify="center"
                                    class="fill-height"
                                    no-gutters
                                >
                                    <v-col cols="12" class="text-center">
                                        <v-btn
                                            icon
                                            variant="text"
                                            @click="
                                                openImageModal(model.id, userId)
                                            "
                                        >
                                            <v-icon>mdi-eye</v-icon>
                                            <v-tooltip
                                                activator="parent"
                                                location="top"
                                            >
                                                View Image
                                            </v-tooltip>
                                        </v-btn>
                                        <v-btn
                                            icon
                                            :color="
                                                model.is_active
                                                    ? 'success'
                                                    : 'primary'
                                            "
                                            variant="text"
                                            :loading="toggleLoading"
                                            @click="
                                                handleToggleActive(
                                                    model.id,
                                                    model
                                                )
                                            "
                                        >
                                            <v-icon>
                                                {{
                                                    model.is_active
                                                        ? "mdi-check-circle"
                                                        : "mdi-circle-edit-outline"
                                                }}
                                            </v-icon>
                                            <v-tooltip
                                                activator="parent"
                                                location="top"
                                            >
                                                {{
                                                    model.is_active
                                                        ? "Active"
                                                        : "Set as Active"
                                                }}
                                            </v-tooltip>
                                        </v-btn>
                                        <v-btn
                                            icon
                                            color="error"
                                            variant="text"
                                            @click="handleDelete(model.id)"
                                        >
                                            <v-icon>mdi-delete</v-icon>
                                            <v-tooltip
                                                activator="parent"
                                                location="top"
                                            >
                                                Delete
                                            </v-tooltip>
                                        </v-btn>
                                    </v-col>
                                </v-row>
                                <v-row no-gutters>
                                    <v-col cols="12" class="date-container">
                                        <v-card-subtitle class="date-text">
                                            Created:
                                            {{ formatDate(model.created_at) }}
                                        </v-card-subtitle>
                                    </v-col>
                                </v-row>
                            </v-card>
                        </v-col>
                    </v-row>
                    <v-alert v-else type="info" class="mt-6">
                        No face models found for this user.
                    </v-alert>
                </v-card-text>
            </v-card>
            <FaceModelImageView
                v-if="selectedFaceModelId && showImageModal"
                v-model="showImageModal"
                :faceModelId="selectedFaceModelId"
                :userId="userId"
            />
        </v-container>
        <v-container v-else>
            <v-alert type="error">
                User ID not found or invalid. Please check the URL.
            </v-alert>
        </v-container>
    </default-layout>
</template>

<script lang="ts">
import { defineComponent, ref, watch, onMounted } from "vue";
import axios from "axios";
import { useRoute, useRouter } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import FaceModelImageView from "@/views/FaceModelImageView.vue";
import BaseButton from "@/components/BaseButton.vue";
import { useAuth } from "@/composables/useAuth";
import { adminMenuItems, userMenuItems } from "@/config/menu";

interface FaceModel {
    id: number;
    image_url: string;
    is_active: boolean;
    user_id: number;
    created_at: string;
}
interface UserDetails {
    username: string;
    name: string;
}

export default defineComponent({
    name: "FaceModelListView",
    components: {
        DefaultLayout,
        FaceModelImageView,
        BaseButton,
    },
    setup() {
        const userId = ref<number | null>(null);
        const route = useRoute();
        const router = useRouter();
        const { logout, isAdmin } = useAuth();
        const loading = ref(true);
        const toggleLoading = ref(false);
        const uploading = ref(false);
        const faceModels = ref<FaceModel[]>([]);
        const showImageModal = ref(false);
        const selectedFaceModelId = ref<number | null>(null);
        const imageFile = ref<File | null>(null);
        const fileInput = ref<any>(null);
        const imageBlobs = ref<{ [key: number]: string }>({});
        const userDetails = ref<UserDetails | null>(null);
        const menuItems = ref(isAdmin.value ? adminMenuItems : userMenuItems);

        // Watch perubahan isAdmin untuk memperbarui menu
        watch(isAdmin, (newValue) => {
            menuItems.value = newValue ? adminMenuItems : userMenuItems;
        });

        // Watch perubahan route params
        watch(
            () => route.params.id,
            (newId) => {
                if (newId) {
                    const parsedId = parseInt(newId as string, 10);
                    if (!isNaN(parsedId)) {
                        userId.value = parsedId;
                        fetchUserDetails();
                    } else {
                        userId.value = null;
                        console.error(
                            "Invalid User ID in route parameters:",
                            newId
                        );
                    }
                } else {
                    userId.value = null;
                    console.error("User ID not found in route parameters.");
                }
            },
            { immediate: true }
        );

        const formatDate = (dateString: string) => {
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        };

        const fetchImage = async (faceModelId: number) => {
            try {
                const response = await axios.get(
                    `/api/face-model/${faceModelId}`,
                    {
                        headers: {
                            Accept: "application/json",
                            "Content-Type": "application/json",
                            Authorization: `Bearer ${localStorage.getItem(
                                "auth_token"
                            )}`,
                        },
                        responseType: "blob",
                    }
                );
                const blobUrl = URL.createObjectURL(response.data);
                imageBlobs.value[faceModelId] = blobUrl;
            } catch (error) {
                console.error(
                    `Failed to fetch image for ID ${faceModelId}:`,
                    error
                );
            }
        };

        const fetchUserDetails = async () => {
            if (!userId.value) return;
            try {
                const response = await axios.get(`/api/users/${userId.value}`, {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                userDetails.value = {
                    username: response.data.user.username,
                    name: response.data.user.name,
                };
            } catch (error) {
                console.error("Failed to fetch user details:", error);
            }
        };

        const fetchFaceModels = async () => {
            if (!userId.value) return;
            loading.value = true;
            try {
                const response = await axios.get(
                    `/api/face-model/user/${userId.value}`,
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
                faceModels.value = response.data;
                for (const model of faceModels.value) {
                    await fetchImage(model.id);
                }
            } catch (error) {
                console.error("Failed to fetch face models:", error);
            } finally {
                loading.value = false;
            }
        };

        const handleToggleActive = async (
            faceModelId: number,
            model: FaceModel
        ) => {
            toggleLoading.value = true;
            try {
                await axios.put(
                    `/api/face-model/${faceModelId}/set-active`,
                    {},
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
                fetchFaceModels();
            } catch (error) {
                console.error("Failed to update face model:", error);
            } finally {
                toggleLoading.value = false;
            }
        };

        const handleImageChange = (event: Event) => {
            const input = event.target as HTMLInputElement;
            if (input.files && input.files.length > 0) {
                const file = input.files[0];
                if (
                    !["image/jpeg", "image/png", "image/jpg"].includes(
                        file.type
                    )
                ) {
                    alert("Please select a valid image file (JPEG, PNG, JPG).");
                    imageFile.value = null;
                    return;
                }
                imageFile.value = file;
            } else {
                imageFile.value = null;
            }
        };

        const handleUpload = async () => {
            if (!imageFile.value || !userId.value) return;
            uploading.value = true;
            try {
                const formData = new FormData();
                formData.append("image", imageFile.value);
                formData.append("user_id", userId.value.toString());
                await axios.post("/api/face-model", formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                fetchFaceModels();
                imageFile.value = null;
                fileInput.value.reset();
            } catch (error) {
                console.error("Failed to upload face model:", error);
            } finally {
                uploading.value = false;
            }
        };

        const openImageModal = (faceModelId: number, userId: number) => {
            selectedFaceModelId.value = faceModelId;
            showImageModal.value = true;
        };

        const handleDelete = async (faceModelId: number) => {
            try {
                await axios.delete(`/api/face-model/${faceModelId}`, {
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                fetchFaceModels();
            } catch (error) {
                console.error("Failed to delete face model:", error);
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

        onMounted(() => {
            fetchFaceModels();
        });

        return {
            menuItems,
            userId,
            handleLogout,
            loading,
            toggleLoading,
            uploading,
            faceModels,
            showImageModal,
            selectedFaceModelId,
            openImageModal,
            imageFile,
            fileInput,
            handleToggleActive,
            handleImageChange,
            handleUpload,
            handleDelete,
            formatDate,
            imageBlobs,
            userDetails,
        };
    },
});
</script>

<style scoped>
.image-card {
    background-size: cover;
    background-position: center;
    height: 200px;
    width: 200px;
    position: relative;
    color: white;
    margin: 0 auto;
}

.image-card::after {
    content: "";
    display: block;
    padding-bottom: 100%;
}

.active-card {
    border: 2px solid #4caf50;
    transition: all 0.3s ease;
}

.v-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.v-row > .v-col {
    padding: 12px;
}

.v-btn {
    margin: 0 8px;
    background-color: rgba(255, 255, 255, 0.8);
}

.image-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    z-index: 0;
}

.v-row {
    position: relative;
    z-index: 1;
}

.date-container {
    position: absolute;
    bottom: 0;
    right: 0;
    padding: 8px;
}

.date-text {
    font-style: italic;
    font-size: 0.875rem;
    color: white;
    background-color: rgba(0, 0, 0, 0.5);
    padding: 2px 6px;
    margin: 0;
}

/* Background card dinamis berdasarkan tema */
:root[data-theme="normal"] .dashboard-card,
:root[data-theme="singleTone"] .dashboard-card {
    background-color: rgba(255, 255, 255, 0.9);
}
:root[data-theme="night"] .dashboard-card {
    background-color: rgba(46, 46, 46, 0.9);
}
</style>
