<template>
    <v-container class="pa-5">
        <v-card elevation="3" class="pa-5" max-width="600">
            <v-card-title class="text-h5">Vuetify Testing Page</v-card-title>
            <v-card-text>
                <p>Test Vuetify components, icons, sizes, and themes.</p>

                <!-- Test Tombol dengan Ikon -->
                <h3 class="mt-4">Buttons with Icons</h3>
                <v-btn
                    color="primary"
                    class="mr-2"
                    prepend-icon="mdi-home"
                    rounded="lg"
                    >Home Button</v-btn
                >
                <v-btn color="secondary" append-icon="mdi-account" rounded="lg"
                    >Account Button</v-btn
                >
                <BaseButton
                    color="primary"
                    prepend-icon="mdi-cog"
                    rounded="lg"
                    class="mr-2 mt-2"
                    @click="() => {}"
                >
                    Settings
                </BaseButton>
                <BaseButton
                    color="accent"
                    append-icon="mdi-star"
                    rounded="lg"
                    class="mr-2 mt-2"
                    @click="() => {}"
                >
                    Favorite
                </BaseButton>

                <!-- Test Input -->
                <h3 class="mt-4">BaseInput</h3>
                <BaseInput
                    v-model="inputValue"
                    label="Test Input"
                    color="primary"
                    placeholder="Enter text"
                    class="mb-4"
                />
                <BaseInput
                    v-model="inputValue2"
                    label="Test Input with Icon"
                    color="secondary"
                    prepend-icon="mdi-magnify"
                    rounded="lg"
                    class="mb-4"
                />

                <!-- Test Rounded Variations (Optional) -->
                <h3 class="mt-4">Rounded Variations</h3>
                <BaseButton
                    color="primary"
                    rounded="md"
                    class="mr-2"
                    @click="() => {}"
                >
                    Rounded MD
                </BaseButton>
                <BaseButton
                    color="primary"
                    rounded="lg"
                    class="mr-2"
                    @click="() => {}"
                >
                    Rounded LG
                </BaseButton>
                <BaseButton
                    color="primary"
                    rounded="pill"
                    class="mr-2"
                    @click="() => {}"
                >
                    Rounded Pill
                </BaseButton>
                <BaseInput
                    v-model="inputValue3"
                    label="Rounded Pill Input"
                    color="primary"
                    rounded="pill"
                    class="mt-4"
                />

                <!-- Test Ukuran Tombol -->
                <h3 class="mt-4">Button Sizes</h3>
                <v-btn color="primary" size="x-small" class="mr-2" rounded="lg"
                    >X-Small</v-btn
                >
                <v-btn color="primary" size="small" class="mr-2" rounded="lg"
                    >Small</v-btn
                >
                <v-btn color="primary" size="default" class="mr-2" rounded="lg"
                    >Default</v-btn
                >
                <v-btn color="primary" size="large" class="mr-2" rounded="lg"
                    >Large</v-btn
                >
                <v-btn color="primary" size="x-large" class="mr-2" rounded="lg"
                    >X-Large</v-btn
                >

                <!-- Test Ukuran BaseButton -->
                <h3 class="mt-4">BaseButton Sizes</h3>
                <BaseButton
                    size="x-small"
                    color="secondary"
                    class="mr-2"
                    rounded="lg"
                    @click="() => {}"
                >
                    X-Small
                </BaseButton>
                <BaseButton
                    size="small"
                    color="secondary"
                    class="mr-2"
                    rounded="lg"
                    @click="() => {}"
                >
                    Small
                </BaseButton>
                <BaseButton
                    size="default"
                    color="secondary"
                    class="mr-2"
                    rounded="lg"
                    @click="() => {}"
                >
                    Default
                </BaseButton>
                <BaseButton
                    size="large"
                    color="secondary"
                    class="mr-2"
                    rounded="lg"
                    @click="() => {}"
                >
                    Large
                </BaseButton>
                <BaseButton
                    size="x-large"
                    color="secondary"
                    class="mr-2"
                    rounded="lg"
                    @click="() => {}"
                >
                    X-Large
                </BaseButton>

                <!-- Test BaseButton dengan Ikon dan Ukuran -->
                <h3 class="mt-4">BaseButton with Icons and Sizes</h3>
                <BaseButton
                    size="small"
                    color="primary"
                    prepend-icon="mdi-cog"
                    rounded="lg"
                    class="mr-2"
                    @click="() => {}"
                >
                    Small Settings
                </BaseButton>
                <BaseButton
                    size="default"
                    color="primary"
                    prepend-icon="mdi-cog"
                    rounded="lg"
                    class="mr-2"
                    @click="() => {}"
                >
                    Default Settings
                </BaseButton>
                <BaseButton
                    size="large"
                    color="primary"
                    prepend-icon="mdi-cog"
                    rounded="lg"
                    class="mr-2"
                    @click="() => {}"
                >
                    Large Settings
                </BaseButton>

                <!-- Test Tema -->
                <h3 class="mt-4">Change Theme</h3>
                <BaseButton
                    size="small"
                    color="primary"
                    variant="outlined"
                    rounded="lg"
                    class="mr-2"
                    @click="setTheme('normal')"
                >
                    Normal Theme
                </BaseButton>
                <BaseButton
                    size="small"
                    color="primary"
                    variant="outlined"
                    rounded="lg"
                    class="mr-2"
                    @click="setTheme('night')"
                >
                    Night Theme
                </BaseButton>
                <BaseButton
                    size="small"
                    color="primary"
                    variant="outlined"
                    rounded="lg"
                    @click="setTheme('singleTone')"
                >
                    Single Tone Theme
                </BaseButton>
            </v-card-text>
        </v-card>
    </v-container>
</template>

<script lang="ts">
import { defineComponent, ref } from "vue";
import { useAppTheme } from "@/composables/useTheme";
import BaseButton from "@/components/BaseButton.vue";
import BaseInput from "@/components/BaseInput.vue";

export default defineComponent({
    name: "Testing",
    components: {
        BaseButton,
        BaseInput,
    },
    setup() {
        const { setTheme } = useAppTheme();
        const inputValue = ref("");
        const inputValue2 = ref("");
        const inputValue3 = ref("");
        return { setTheme, inputValue, inputValue2, inputValue3 };
    },
});
</script>

<style scoped>
.v-container {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>
