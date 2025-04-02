import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import { aliases, mdi } from "vuetify/iconsets/mdi";

export const defaultTheme = {
    dark: false,
    colors: {
        primary: "#E57373",
        secondary: "#424242",
        accent: "#FFCDD2",
        error: "#F44336",
        info: "#2196F3",
        success: "#4CAF50",
        warning: "#FFC107",
        background: "#FAFAFA",
        surface: "#FFFFFF",
        "on-primary": "#FFFFFF",
        "on-secondary": "#FFFFFF",
        "on-accent": "#000000",
        "on-error": "#FFFFFF",
        "on-info": "#FFFFFF",
        "on-success": "#FFFFFF",
        "on-warning": "#000000",
        "on-background": "#000000",
        "on-surface": "#000000",
    },
    variables: {
        "primary-lighten-1": "#EF9A9A",
        "primary-lighten-2": "#FFCDD2",
        "primary-darken-1": "#EF5350",
        "primary-darken-2": "#F44336",
        "secondary-lighten-1": "#616161",
        "secondary-lighten-2": "#757575",
        "secondary-darken-1": "#212121",
        "secondary-darken-2": "#000000",
    },
};

export const darkTheme = {
    dark: true,
    colors: {
        primary: "#BB86FC",
        secondary: "#03DAC6",
        accent: "#03DAC6",
        error: "#CF6679",
        info: "#2196F3",
        success: "#4CAF50",
        warning: "#FFC107",
        background: "#121212",
        surface: "#1E1E1E",
        "on-primary": "#000000",
        "on-secondary": "#000000",
        "on-accent": "#000000",
        "on-error": "#000000",
        "on-info": "#FFFFFF",
        "on-success": "#FFFFFF",
        "on-warning": "#000000",
        "on-background": "#FFFFFF",
        "on-surface": "#FFFFFF",
    },
    variables: {
        "primary-lighten-1": "#BB86FC",
        "primary-lighten-2": "#CF94DA",
        "primary-darken-1": "#9A67EA",
        "primary-darken-2": "#7C4DFF",
        "secondary-lighten-1": "#64FFDA",
        "secondary-lighten-2": "#A7FFEB",
        "secondary-darken-1": "#00BFA5",
        "secondary-darken-2": "#00897B",
    },
};

export const vuetify = createVuetify({
    components,
    directives,
    icons: {
        defaultSet: "mdi",
        aliases,
        sets: {
            mdi,
        },
    },
    theme: {
        defaultTheme: "light", // Set the default theme to light
        themes: {
            light: defaultTheme, // Register the light theme
            dark: darkTheme, // Register the dark theme
        },
    },
    defaults: {
        VBtn: {
            color: "primary",
            variant: "elevated",
            class: "text-white",
        },
        VCard: {
            color: "surface",
        },
        VAppBar: {
            color: "primary",
        },
        VNavigationDrawer: {
            color: "background",
        },
        VList: {
            color: "surface",
        },
        VListItem: {
            color: "surface",
        },
        VTextField: {
            color: "primary",
        },
        VSelect: {
            color: "primary",
        },
        VCheckbox: {
            color: "primary",
        },
        VRadio: {
            color: "primary",
        },
        VSwitch: {
            color: "primary",
        },
    },
});
