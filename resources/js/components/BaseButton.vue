<template>
    <v-btn
        :color="color"
        :size="size"
        :variant="variant"
        :disabled="disabled"
        :loading="loading"
        :block="block"
        :prepend-icon="prependIcon"
        :append-icon="appendIcon"
        :rounded="rounded"
        :class="['v-btn-custom', customClass]"
        @click="$emit('click')"
    >
        <slot />
    </v-btn>
</template>

<script lang="ts">
import { defineComponent, PropType } from "vue";

export default defineComponent({
    name: "BaseButton",
    props: {
        color: {
            type: String,
            default: "primary",
        },
        size: {
            type: String as PropType<
                "x-small" | "small" | "default" | "large" | "x-large"
            >,
            default: "default",
        },
        variant: {
            type: String as PropType<
                "flat" | "elevated" | "tonal" | "outlined" | "text" | "plain"
            >,
            default: "elevated",
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        loading: {
            type: Boolean,
            default: false,
        },
        block: {
            type: Boolean,
            default: false,
        },
        customClass: {
            type: String,
            default: "",
        },
        prependIcon: {
            type: String,
            default: undefined,
        },
        appendIcon: {
            type: String,
            default: undefined,
        },
        rounded: {
            type: String as PropType<"0" | "sm" | "md" | "lg" | "xl" | "pill">,
            default: "lg",
        },
    },
    emits: ["click"],
});
</script>

<style scoped>
.v-btn-custom {
    padding-left: 12px !important;
    padding-right: 12px !important;
}
.v-btn-custom :deep(.v-btn__prepend),
.v-btn-custom :deep(.v-btn__append) {
    margin-right: 8px;
    margin-left: 8px;
}
</style>
