<template>
    <v-dialog v-model="dialog" max-width="600">
        <v-card>
            <v-card-title>
                Face Model Image
                <v-spacer></v-spacer>
                <v-btn icon="mdi-close" @click="closeModal"></v-btn>
            </v-card-title>
            <v-card-text>
                <v-progress-linear
                    v-if="loading"
                    indeterminate
                    color="primary"
                ></v-progress-linear>
                <div v-else>
                    <v-img
                        v-if="imageUrl"
                        :src="imageUrl"
                        aspect-ratio="1"
                        class="grey lighten-2"
                        cover
                        @error="handleImageError"
                    >
                    </v-img>
                    <v-icon v-else>mdi-image-broken</v-icon>
                </div>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script lang="ts">
import { defineComponent, ref, watch, onMounted } from "vue";
import axios from "axios";

export default defineComponent({
    name: "FaceModelImageView",
    props: {
        faceModelId: {
            type: Number,
            required: true,
        },
        userId: {
            type: Number,
            required: true,
        },
        modelValue: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["update:modelValue"],
    setup(props, { emit }) {
        const loading = ref(true);
        const imageUrl = ref<string | null>(null);
        const dialog = ref(props.modelValue);

        watch(
            () => props.modelValue,
            (newVal) => {
                dialog.value = newVal;
            }
        );

        watch(dialog, (newVal) => {
            emit("update:modelValue", newVal);
        });

        const fetchImage = async () => {
            if (!props.faceModelId) return;
            loading.value = true;
            try {
                const response = await axios.get(
                    `/api/face-model/${props.faceModelId}`,
                    {
                        headers: {
                            Accept: "application/json",
                            "Content-Type": "application/json",
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                        },
                        responseType: "blob", // Important for handling image data
                    }
                );
                // Create a URL for the image blob
                imageUrl.value = URL.createObjectURL(response.data);
            } catch (error) {
                console.error("Failed to fetch image:", error);
            } finally {
                loading.value = false;
            }
        };

        const handleImageError = () => {
            console.error(`Failed to load image`);
        };

        const closeModal = () => {
            emit("update:modelValue", false);
        };

        onMounted(() => {
            fetchImage();
        });

        return {
            loading,
            imageUrl,
            handleImageError,
            dialog,
            closeModal,
        };
    },
});
</script>
