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

                <v-text-field
                    v-model="formData.image_url"
                    label="Image URL"
                    variant="outlined"
                    full-width
                    class="mb-4"
                ></v-text-field>

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
        };
    },
    methods: {
        async handleSubmit() {
            const form = this.$refs.form as VForm;
            const { valid } = await form.validate();
            if (valid) {
                this.$emit("submit", this.formData);
            }
        },
    },
});
</script>
