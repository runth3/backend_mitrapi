<template>
    <v-snackbar
        v-model="internalModel"
        :color="color"
        :timeout="timeout"
        :location="location"
        :vertical="vertical"
        v-bind="$attrs"
    >
        <slot />
        <template v-slot:actions>
            <v-btn
                v-if="closable"
                color="white"
                variant="text"
                @click="internalModel = false"
            >
                Close
            </v-btn>
        </template>
    </v-snackbar>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from "vue";

export default defineComponent({
    name: "BaseSnackbar",
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        color: {
            type: String,
            default: "info", // Default ke 'info' (bisa 'success', 'error', dll.)
        },
        timeout: {
            type: Number,
            default: 3000, // 3 detik
        },
        location: {
            type: String,
            default: "bottom right", // Posisi default
        },
        vertical: {
            type: Boolean,
            default: false, // Apakah snackbar vertikal
        },
        closable: {
            type: Boolean,
            default: true, // Apakah ada tombol close
        },
    },
    emits: ["update:modelValue"],
    setup(props, { emit }) {
        const internalModel = ref(props.modelValue);

        watch(
            () => props.modelValue,
            (newValue) => {
                internalModel.value = newValue;
            }
        );

        watch(internalModel, (newValue) => {
            emit("update:modelValue", newValue);
        });

        return {
            internalModel,
        };
    },
});
</script>
