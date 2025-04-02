// filepath: /Users/randihartono/Desktop/src/mitrapi_xg/laravel/resources/js/router/index.ts
import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
import Login from "../views/login.vue";
import Dashboard from "../views/dashboard.vue";
import News from "../views/news.vue"; // Import the News component

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
    {
        path: "/news",
        name: "News",
        component: News,
        meta: { requiresAuth: true },
    },
    {
        path: "/news/:id",
        name: "NewsDetail",
        component: () => import("@/views/news-detail.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/news/create",
        name: "CreateNews",
        component: () => import("@/views/create-news.vue"), // Create this component for adding news
        meta: { requiresAuth: true },
    },
    {
        path: "/users",
        name: "Users",
        component: () => import("@/views/users.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/profile",
        name: "Profile",
        component: () => import("@/views/profile.vue"),
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard to check authentication
router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem("token"); // Updated to use 'token' instead of 'auth_token'
    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: "Login" });
    } else {
        next();
    }
});

export default router;
