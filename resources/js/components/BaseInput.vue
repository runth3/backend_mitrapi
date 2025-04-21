<template>
    <v-text-field
        :model-value="modelValue"
        :color="color"
        :label="label"
        :variant="variant"
        :disabled="disabled"
        :readonly="readonly"
        :placeholder="placeholder"
        :type="type"
        :rules="rules"
        :rounded="rounded"
        :class="customClass"
        @update:model-value="$emit('update:modelValue', $event)"
        @input="$emit('input', $event)"
    />
</template>

<script lang="ts">
import { defineComponent, PropType } from "vue";

export default defineComponent({
    name: "BaseInput",
    props: {
        modelValue: {
            type: [String, Number] as PropType<string | number>,
            default: "",
        },
        color: {
            type: String,
            default: "primary",
        },
        label: {
            type: String,
            default: "",
        },
        variant: {
            type: String as PropType<
                "filled" | "outlined" | "plain" | "underlined" | "solo"
            >,
            default: "outlined",
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        readonly: {
            type: Boolean,
            default: false,
        },
        placeholder: {
            type: String,
            default: "",
        },
        type: {
            type: String,
            default: "text",
        },
        customClass: {
            type: String,
            default: "",
        },
        rounded: {
            type: String as PropType<"0" | "sm" | "md" | "lg" | "xl" | "pill">,
            default: "lg",
        },
        rules: {
            type: Array as PropType<Array<(v: string) => string | boolean>>,
            default: () => [],
        },
    },
    emits: ["update:modelValue", "input"],
});
</script>
