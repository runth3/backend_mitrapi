import "./bootstrap";
import { createApp } from "vue";
import App from "./App.vue";
import vuetify from "./plugins/vuetify"; // Import Vuetify plugin
import router from "./router";
import BaseInput from "@/components/BaseInput.vue";
import BaseButton from "@/components/BaseButton.vue";

const app = createApp(App);

app.component("BaseInput", BaseInput);
app.component("BaseButton", BaseButton);

app.use(vuetify); // Use Vuetify
app.use(router);
app.mount("#app");
