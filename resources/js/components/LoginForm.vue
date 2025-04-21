<template>
    <v-form @submit.prevent="$emit('submit')">
        <BaseInput
            v-model="localUsername"
            label="Username"
            color="primary"
            type="text"
            rounded="lg"
            class="mb-4"
            :rules="[(v: string) => !!v || 'Username is required']"
            @keypress.enter="moveToPassword"
        />
        <BaseInput
            ref="passwordInput"
            v-model="localPassword"
            label="Password"
            color="primary"
            type="password"
            rounded="lg"
            class="mb-4"
            :rules="[(v: string) => !!v || 'Password is required']"
            @keypress.enter="$emit('submit')"
        />
        <BaseButton
            type="submit"
            size="default"
            color="primary"
            block
            rounded="lg"
            prepend-icon="mdi-login"
            :loading="loading"
            :disabled="loading || !localUsername || !localPassword"
        >
            Login
        </BaseButton>
        <v-alert
            v-if="error"
            type="error"
            text-color="on-surface"
            class="mt-4 text-body-2"
            density="compact"
            closable
            @click:close="$emit('update:error', null)"
        >
            {{ error }}
        </v-alert>
    </v-form>
</template>

<script lang="ts">
import { defineComponent, PropType, ref, watch } from "vue";
import BaseInput from "@/components/BaseInput.vue";
import BaseButton from "@/components/BaseButton.vue";

export default defineComponent({
    name: "LoginForm",
    components: {
        BaseInput,
        BaseButton,
    },
    props: {
        username: {
            type: String,
            default: "",
        },
        password: {
            type: String,
            default: "",
        },
        error: {
            type: [String, null] as PropType<string | null | undefined>,
            default: null,
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["update:username", "update:password", "update:error", "submit"],
    setup(props, { emit }) {
        const localUsername = ref(props.username);
        const localPassword = ref(props.password);
        const passwordInput = ref<InstanceType<typeof BaseInput> | null>(null);

        watch(
            () => props.username,
            (newValue) => {
                localUsername.value = newValue;
            }
        );

        watch(
            () => props.password,
            (newValue) => {
                localPassword.value = newValue;
            }
        );

        watch(localUsername, (newValue) => {
            emit("update:username", newValue);
        });

        watch(localPassword, (newValue) => {
            emit("update:password", newValue);
        });

        function moveToPassword(event: KeyboardEvent) {
            event.preventDefault();
            if (passwordInput.value) {
                const inputElement = (
                    passwordInput.value.$el as HTMLElement
                ).querySelector("input");
                if (inputElement) {
                    inputElement.focus();
                }
            }
        }

        return {
            localUsername,
            localPassword,
            passwordInput,
            moveToPassword,
        };
    },
});
</script>
