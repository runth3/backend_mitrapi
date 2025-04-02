import "./bootstrap";
import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import { vuetify } from "./theme";
import "@mdi/font/css/materialdesignicons.css";

const app = createApp(App);

app.use(router);
app.use(vuetify);

app.mount("#app");
