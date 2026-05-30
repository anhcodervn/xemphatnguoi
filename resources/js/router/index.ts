import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
import adminRouter from "./modules/admin";
import clientRouter from "./modules/client";

const routes: RouteRecordRaw[] = [
    adminRouter,
    clientRouter
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    console.log("Navigating to:", to);
});

router.onError((error, to) => {
    console.error(
        `Import component thất bại${to?.fullPath ? `: ${to.fullPath}` : ""}`,
        error,
    );
});

export default router;
