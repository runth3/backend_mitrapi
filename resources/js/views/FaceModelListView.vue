<template>
    <default-layout
        title="Face Model List"
        :menu-items="menuItems"
        @logout="logout"
    >
        <v-container v-if="userId">
            <v-card>
                <v-card-title v-if="userDetails"
                    >Face Models: {{ userDetails.username }}/{{
                        userDetails.name
                    }}</v-card-title
                >
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
                            <v-btn
                                color="primary"
                                class="mt-2"
                                :loading="uploading"
                                :disabled="!imageFile"
                                @click="handleUpload"
                            >
                                Upload
                            </v-btn>
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
import DefaultLayout from "../layouts/DefaultLayout.vue";
import { menuItems } from "../config/menu";
import { useRoute, useRouter } from "vue-router";
import FaceModelImageView from "./FaceModelImageView.vue";

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
    },
    setup() {
        const userId = ref<number | null>(null);
        const route = useRoute();
        const router = useRouter();
        const loading = ref(true);
        const toggleLoading = ref(false);
        const uploading = ref(false);
        const faceModels = ref<FaceModel[]>([]);
        const showImageModal = ref(false);
        const selectedFaceModelId = ref<number | null>(null);
        const imageFile = ref<File | null>(null);
        const fileInput = ref<any>(null);
        const imageBlobs = ref<{ [key: number]: string }>({}); // Simpan URL blob untuk setiap gambar
        const userDetails = ref<UserDetails | null>(null);

        // Fungsi untuk memformat tanggal
        const formatDate = (dateString: string) => {
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            }); // Contoh: "Apr 3, 2025"
        };

        // Fungsi untuk mengambil gambar sebagai blob
        const fetchImage = async (faceModelId: number) => {
            try {
                const response = await axios.get(
                    `/api/face-model/${faceModelId}`,
                    {
                        headers: {
                            Accept: "application/json",
                            "Content-Type": "application/json",
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                        },
                        responseType: "blob", // Untuk gambar
                    }
                );
                const blobUrl = URL.createObjectURL(response.data);
                imageBlobs.value[faceModelId] = blobUrl;
                console.log(`Image fetched for ID ${faceModelId}:`, blobUrl);
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
                            "token"
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

        watch(
            () => route.params.id,
            (newId) => {
                if (newId) {
                    const parsedId = parseInt(newId as string, 10);
                    if (!isNaN(parsedId)) {
                        userId.value = parsedId;
                        console.log("userId set to:", userId.value);
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
                                "token"
                            )}`,
                        },
                    }
                );
                console.log("API Response:", response.data);
                faceModels.value = response.data;
                console.log("faceModels after fetch:", faceModels.value);

                // Fetch gambar untuk setiap face model
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
                const response = await axios.put(
                    `/api/face-model/${faceModelId}/set-active`,
                    {},
                    {
                        headers: {
                            Accept: "application/json",
                            "Content-Type": "application/json",
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                        },
                    }
                );
                console.log("Toggle Response:", response.data);
                fetchFaceModels(); // Refresh data termasuk gambar
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
                console.log("Selected file:", file);
                console.log("File type:", file.type);
                console.log("File size:", file.size);

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
                console.warn("No file selected.");
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
                const response = await axios.post("/api/face-model", formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                    },
                });
                console.log("Upload successful:", response.data);
                fetchFaceModels();
                imageFile.value = null;
                fileInput.value.reset();
            } catch (error: any) {
                console.error("Failed to upload face model:", error);
                if (error.response) {
                    console.error("Error Response:", error.response);
                    console.error("Error Response Data:", error.response.data);
                }
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
                            "token"
                        )}`,
                    },
                });
                console.log("Face model deleted:", faceModelId);
                fetchFaceModels();
            } catch (error) {
                console.error("Failed to delete face model:", error);
            }
        };

        const handleThumbnailError = (model: FaceModel) => {
            console.error(`Failed to load thumbnail for model ID: ${model.id}`);
        };

        const logout = () => {
            localStorage.removeItem("token");
            router.push({ name: "Login" });
        };

        onMounted(() => {
            console.log("Component mounted, fetching face models...");
            fetchFaceModels();
        });

        return {
            menuItems,
            userId,
            logout,
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
            handleThumbnailError,
            imageBlobs,
            fetchImage,
            userDetails,
        };
    },
});
</script>
<style scoped>
.image-card {
    background-size: cover;
    background-position: center;
    height: 200px; /* Fixed height for 1:1 ratio */
    width: 200px; /* Fixed width for 1:1 ratio */
    position: relative;
    color: white;
    margin: 0 auto; /* Center the card horizontally in its column */
}

/* Ensure the card maintains 1:1 aspect ratio */
.image-card::after {
    content: "";
    display: block;
    padding-bottom: 100%; /* Creates 1:1 aspect ratio */
}

/* Efek untuk card yang aktif */
.active-card {
    border: 2px solid #4caf50; /* Warna hijau untuk menandakan aktif */
    transition: all 0.3s ease;
}

/* Hover effect untuk card */
.v-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

/* Jarak antar card */
.v-row > .v-col {
    padding: 12px;
}

/* Styling untuk tombol */
.v-btn {
    margin: 0 8px; /* Jarak antar tombol */
    background-color: rgba(
        255,
        255,
        255,
        0.8
    ); /* Latar tombol semi-transparan */
}

/* Overlay untuk meningkatkan keterbacaan */
.image-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3); /* Overlay gelap untuk kontras */
    z-index: 0;
}

/* Pastikan konten di atas overlay */
.v-row {
    position: relative;
    z-index: 1;
}

/* Style for date at bottom right */
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
    background-color: rgba(
        0,
        0,
        0,
        0.5
    ); /* Slight background for readability */
    padding: 2px 6px;
    margin: 0;
}
</style>
