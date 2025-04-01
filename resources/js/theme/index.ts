import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";

export const defaultTheme = {
    primary: "#E57373",
    "primary-lighten-1": "#EF9A9A",
    "primary-lighten-2": "#FFCDD2",
    "primary-darken-1": "#EF5350",
    "primary-darken-2": "#F44336",
    secondary: "#424242",
    "secondary-lighten-1": "#616161",
    "secondary-lighten-2": "#757575",
    "secondary-darken-1": "#212121",
    "secondary-darken-2": "#000000",
    background: "#FAFAFA",
    surface: "#FFFFFF",
};

export const vuetify = createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: "light",
        themes: {
            light: {
                colors: defaultTheme,
            },
        },
    },
});
