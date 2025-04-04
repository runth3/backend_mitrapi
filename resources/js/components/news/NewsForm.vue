<template>
    <v-card>
        <v-card-title>{{ title }}</v-card-title>
        <v-card-text>
            <v-form ref="form" v-model="valid" @submit.prevent="handleSubmit">
                <v-text-field
                    v-model="formData.title"
                    :rules="[(v) => !!v || 'Title is required']"
                    label="Title"
                    required
                    variant="outlined"
                    full-width
                    class="mb-4"
                ></v-text-field>

                <v-textarea
                    v-model="formData.content"
                    :rules="[(v) => !!v || 'Content is required']"
                    label="Content"
                    required
                    variant="outlined"
                    full-width
                    class="mb-4"
                    rows="10"
                    auto-grow
                ></v-textarea>

                <v-file-input
                    v-model="imageFile"
                    :rules="imageRules"
                    accept="image/*"
                    label="News Image"
                    variant="outlined"
                    prepend-icon="mdi-camera"
                    full-width
                    class="mb-4"
                    @change="handleImageChange"
                    :show-size="true"
                >
                    <template v-slot:selection="{ fileNames }">
                        <template v-for="fileName in fileNames" :key="fileName">
                            <v-chip
                                size="small"
                                label
                                color="primary"
                                class="me-2"
                            >
                                {{ fileName }}
                            </v-chip>
                        </template>
                    </template>
                </v-file-input>

                <!-- Preview image if available -->
                <v-img
                    v-if="imagePreview"
                    :src="imagePreview"
                    max-height="200"
                    contain
                    class="mb-4"
                ></v-img>

                <v-row>
                    <v-col cols="12" class="d-flex justify-end">
                        <v-btn
                            color="secondary"
                            variant="text"
                            class="mr-2"
                            @click="$emit('cancel')"
                        >
                            Cancel
                        </v-btn>
                        <v-btn
                            color="primary"
                            type="submit"
                            :loading="loading"
                            :disabled="!valid"
                        >
                            {{ submitButtonText }}
                        </v-btn>
                    </v-col>
                </v-row>
            </v-form>
        </v-card-text>
    </v-card>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import type { VForm } from "vuetify/components";

export interface NewsFormData {
    title: string;
    content: string;
    image_url?: string;
}

export default defineComponent({
    name: "NewsForm",
    props: {
        title: {
            type: String,
            default: "Create News",
        },
        submitButtonText: {
            type: String,
            default: "Create",
        },
        initialData: {
            type: Object as () => NewsFormData,
            default: () => ({
                title: "",
                content: "",
                image_url: "",
            }),
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            valid: false,
            formData: { ...this.initialData },
            imageFile: null as File | null,
            imagePreview: null as string | null,
            imageRules: [
                (value: File) => {
                    if (!value) return true; // Optional file

                    // Check file size (max 5MB)
                    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                    if (value.size > maxSize)
                        return "Image size should be less than 5MB!";

                    // Check file type
                    const allowedTypes = [
                        "image/jpeg",
                        "image/png",
                        "image/gif",
                        "image/webp",
                    ];
                    if (!allowedTypes.includes(value.type)) {
                        return "Please upload an image file (JPEG, PNG, GIF, WEBP)";
                    }

                    return true;
                },
            ],
        };
    },
    methods: {
        handleImageChange(file: File | null) {
            if (file) {
                // Create preview URL
                this.imagePreview = URL.createObjectURL(file);
                this.imageFile = file;
            } else {
                this.clearImage();
            }
        },
        clearImage() {
            if (this.imagePreview) {
                URL.revokeObjectURL(this.imagePreview);
            }
            this.imagePreview = null;
            this.imageFile = null;
        },
        async handleSubmit() {
            const form = this.$refs.form as VForm;
            const { valid } = await form.validate();

            if (valid) {
                // Create FormData object to handle file upload
                const formData = new FormData();
                formData.append("title", this.formData.title);
                formData.append("content", this.formData.content);

                if (this.imageFile) {
                    formData.append("image", this.imageFile);
                }

                this.$emit("submit", formData);
            }
        },
    },
    beforeUnmount() {
        // Clean up any object URLs when component is destroyed
        this.clearImage();
    },
    watch: {
        initialData: {
            handler(newVal) {
                this.formData = { ...newVal };
                // If there's an initial image_url, show it in preview
                if (newVal.image_url) {
                    this.imagePreview = newVal.image_url;
                }
            },
            immediate: true,
        },
    },
});
</script>
