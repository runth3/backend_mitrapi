<template>
    <v-btn
        :type="type || 'button'"
        :color="color || 'primary'"
        :block="block"
        :elevation="elevation"
        class="rounded-pill py-3 px-6 text-body-large"
        :style="{ minHeight: '56px', minWidth: '300px' }"
    >
        <slot />
    </v-btn>
</template>

<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({
    name: "BaseButton",
    props: {
        type: {
            type: String,
            default: "button", // Default type is "button"
        },
        color: {
            type: String,
            default: "primary", // Default color is "primary"
        },
        block: {
            type: Boolean,
            default: true, // Default block is true
        },
        elevation: {
            type: Number,
            default: 2, // Default elevation is 2
        },
    },
});
</script>

<style scoped>
/* Add any custom styles for the button if needed */
</style>
