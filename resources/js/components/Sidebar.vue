<!-- filepath: /Users/randihartono/Desktop/src/mitrapi_xg/laravel/resources/js/components/Sidebar.vue -->
<template>
    <v-navigation-drawer v-model="drawer" app :rail="isRail" permanent>
        <v-list-item>
            <v-list-item-title class="text-h6">Menu</v-list-item-title>
            <template v-slot:append>
                <v-btn
                    variant="text"
                    :icon="isRail ? 'mdi-chevron-right' : 'mdi-chevron-left'"
                    @click.stop="toggleRail"
                    class="rail-toggle-btn"
                ></v-btn>
            </template>
        </v-list-item>

        <v-divider></v-divider>

        <v-list density="compact" nav>
            <v-list-item
                v-for="item in menuItems"
                :key="item.title"
                :to="item.path"
                :prepend-icon="item.icon"
                :title="item.title"
                :active="$route.path === item.path"
            ></v-list-item>
        </v-list>
    </v-navigation-drawer>
</template>

<script lang="ts">
import { defineComponent } from "vue";

interface MenuItem {
    title: string;
    icon: string;
    path: string;
}

export default defineComponent({
    name: "Sidebar",
    props: {
        menuItems: {
            type: Array as () => MenuItem[],
            required: true,
        },
        modelValue: {
            type: Boolean,
            required: true,
        },
    },
    emits: ["update:modelValue"],
    data() {
        return {
            isRail: false,
        };
    },
    computed: {
        drawer: {
            get() {
                return this.modelValue;
            },
            set(value: boolean) {
                this.$emit("update:modelValue", value);
            },
        },
    },
    methods: {
        toggleRail() {
            this.isRail = !this.isRail;
        },
    },
});
</script>

<style scoped>
.v-navigation-drawer {
    background-color: var(--v-theme-background);
}

.v-list-item--active {
    background-color: var(--v-theme-primary-lighten-2);
}

/* Add hover effect to show title when minimized */
.v-navigation-drawer--rail .v-list-item:hover {
    background-color: var(--v-theme-primary-lighten-2);
}

.rail-toggle-btn {
    margin-right: -8px;
}

.v-navigation-drawer--rail .rail-toggle-btn {
    margin-right: 0;
}
</style>
