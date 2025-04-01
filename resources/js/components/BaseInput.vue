<template>
    <v-text-field
        :model-value="modelValue"
        :label="label"
        :type="type"
        :variant="variant || 'outlined'"
        :dense="dense !== false"
        :rounded="rounded !== false"
        :style="{ minHeight: '56px', minWidth: '300px' }"
        class="mb-4"
        @update:model-value="$emit('update:modelValue', $event)"
    ></v-text-field>
</template>

<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({
    name: "BaseInput",
    props: {
        modelValue: {
            type: [String, Number],
            required: true, // Ensure v-model is always passed
        },
        label: {
            type: String,
            default: "", // Default label is an empty string
        },
        type: {
            type: String,
            default: "text", // Default type is "text"
        },
        variant: {
            type: String as () =>
                | "outlined"
                | "filled"
                | "plain"
                | "underlined"
                | "solo"
                | "solo-inverted"
                | "solo-filled"
                | undefined,
            default: "outlined", // Default variant is "outlined"
        },
        dense: {
            type: Boolean,
            default: true, // Default dense is true
        },
        rounded: {
            type: Boolean,
            default: true, // Default rounded is true
        },
    },
    emits: ["update:modelValue"], // Emit event for v-model binding
});
</script>

<style scoped>
/* Add any custom styles for the input if needed */
</style>
