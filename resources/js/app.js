import "./bootstrap";
import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import { createVuetify } from "vuetify";
import "vuetify/styles"; // Import Vuetify styles
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";

const vuetify = createVuetify({
    components,
    directives,
});

const app = createApp(App);
app.use(router);
app.use(vuetify);
app.mount("#app");
