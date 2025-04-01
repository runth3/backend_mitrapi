// filepath: /Users/randihartono/Desktop/src/mitrapi_xg/laravel/resources/js/router/index.ts
import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
import Login from "../views/login.vue";
import Dashboard from "../views/dashboard.vue";

// Define routes with TypeScript types
const routes: Array<RouteRecordRaw> = [
    {
        path: "/",
        name: "Login",
        component: Login,
    },
    {
        path: "/dashboard",
        name: "Dashboard",
        component: Dashboard,
        meta: { requiresAuth: true }, // Protect this route
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard to check authentication
router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem("auth_token"); // Check if the user is logged in
    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: "Login" });
    } else {
        next();
    }
});

export default router;
