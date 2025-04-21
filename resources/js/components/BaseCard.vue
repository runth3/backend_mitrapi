<template>
    <v-card class="dashboard-card" elevation="3" v-bind="$attrs">
        <slot></slot>
    </v-card>
</template>

<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({
    name: "BaseCard",
    inheritAttrs: false,
});
</script>

<style scoped>
/* Background card dinamis berdasarkan tema */
:root[data-theme="normal"] .dashboard-card,
:root[data-theme="singleTone"] .dashboard-card {
    background-color: rgba(255, 255, 255, 0.9);
}
:root[data-theme="night"] .dashboard-card {
    background-color: rgba(46, 46, 46, 0.9);
}
</style>
