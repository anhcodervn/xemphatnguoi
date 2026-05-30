import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "@/App.vue";
import router from "./router";

const app = createApp(App);

app.config.errorHandler = (error, instance, info) => {
    console.error("Vue component error:", { error, instance, info });
};

app.use(createPinia());
app.use(router);

app.mount("#app");
