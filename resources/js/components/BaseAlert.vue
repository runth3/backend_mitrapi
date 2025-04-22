<template>
    <v-alert
        :type="type"
        :text-color="textColor"
        :density="density"
        :closable="closable"
        v-bind="$attrs"
    >
        <slot />
    </v-alert>
</template>

<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({
    name: "BaseAlert",
    props: {
        type: {
            type: String,
            default: "info", // Default ke 'info' (bisa 'error', 'success', 'warning', dll.)
        },
        textColor: {
            type: String,
            default: "on-surface", // Warna teks default
        },
        density: {
            type: String,
            default: "compact", // Density default untuk Vuetify 3
        },
        closable: {
            type: Boolean,
            default: false, // Apakah alert bisa ditutup
        },
    },
    inheritAttrs: false,
});
</script>
