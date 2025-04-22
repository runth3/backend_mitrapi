<template>
    <div class="d-flex justify-start">
        <v-tooltip location="top">
            <template v-slot:activator="{ props }">
                <BaseButton
                    v-bind="props"
                    size="small"
                    color="primary"
                    variant="tonal"
                    prepend-icon="mdi-palette"
                    rounded="lg"
                    class="theme-button mr-2"
                    aria-label="Normal Theme"
                    @click="setTheme('normal')"
                >
                    <span class="visually-hidden">Normal Theme</span>
                </BaseButton>
            </template>
            <span>Normal Theme</span>
        </v-tooltip>
        <v-tooltip location="top">
            <template v-slot:activator="{ props }">
                <BaseButton
                    v-bind="props"
                    size="small"
                    color="primary"
                    variant="tonal"
                    prepend-icon="mdi-moon-waning-crescent"
                    rounded="lg"
                    class="theme-button mr-2"
                    aria-label="Night Theme"
                    @click="setTheme('night')"
                >
                    <span class="visually-hidden">Night Theme</span>
                </BaseButton>
            </template>
            <span>Night Theme</span>
        </v-tooltip>
        <v-tooltip location="top">
            <template v-slot:activator="{ props }">
                <BaseButton
                    v-bind="props"
                    size="small"
                    color="primary"
                    variant="tonal"
                    prepend-icon="mdi-contrast-circle"
                    rounded="lg"
                    class="theme-button"
                    aria-label="Single Tone Theme"
                    @click="setTheme('singleTone')"
                >
                    <span class="visually-hidden">Single Tone Theme</span>
                </BaseButton>
            </template>
            <span>Single Tone Theme</span>
        </v-tooltip>
    </div>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import BaseButton from "@/components/BaseButton.vue";
import { useAppTheme } from "@/composables/useTheme";

export default defineComponent({
    name: "ThemeSelector",
    components: {
        BaseButton,
    },
    setup() {
        const { setTheme } = useAppTheme();

        return {
            setTheme,
        };
    },
});
</script>

<style scoped>
.theme-button {
    min-width: 40px;
}

.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
</style>