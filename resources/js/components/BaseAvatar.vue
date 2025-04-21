<template>
    <v-avatar
        :size="size"
        :color="isDarkTheme ? 'grey-darken-3' : 'grey-darken-1'"
        v-bind="$attrs"
    >
        <v-icon :size="size">mdi-account</v-icon>
    </v-avatar>
</template>

<script lang="ts">
import { defineComponent, computed } from "vue";
import { useAppTheme } from "@/composables/useTheme";

export default defineComponent({
    name: "BaseAvatar",
    props: {
        size: {
            type: [String, Number],
            default: "32",
        },
    },
    setup() {
        const { currentTheme } = useAppTheme();
        const isDarkTheme = computed(() => currentTheme.value === "night");

        return {
            isDarkTheme,
        };
    },
});
</script>
