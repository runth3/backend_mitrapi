import { ref, watch, Ref } from "vue";
import { useTheme as useVuetifyTheme } from "vuetify";

export function useAppTheme() {
    const vuetifyTheme = useVuetifyTheme();
    const currentTheme: Ref<string> = ref(
        localStorage.getItem("theme") || "normal"
    );

    watch(currentTheme, (newTheme) => {
        vuetifyTheme.global.name.value = newTheme;
        localStorage.setItem("theme", newTheme);
    });

    function setTheme(theme: "normal" | "night" | "singleTone") {
        currentTheme.value = theme;
    }

    return {
        currentTheme,
        setTheme,
    };
}
