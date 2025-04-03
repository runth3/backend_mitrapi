// filepath: /Users/randihartono/Desktop/src/mitrapi_xg/laravel/resources/js/router/index.ts
import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
// Define routes with TypeScript types
const routes: Array<RouteRecordRaw> = [
    {
        path: "/",
        name: "Login",
        component: () => import("../views/login.vue"), // Lazy loading
    },
    {
        path: "/dashboard",
        name: "Dashboard",
        component: () => import("../views/dashboard.vue"), // Lazy loading
        meta: { requiresAuth: true },
    },
    {
        path: "/news",
        name: "News",
        component: () => import("../views/news.vue"), // Lazy loading
        meta: { requiresAuth: true },
    },
    {
        path: "/news/:id",
        name: "NewsDetail",
        component: () => import("../views/news-detail.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/news/create",
        name: "CreateNews",
        component: () => import("../views/create-news.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/users",
        name: "Users",
        component: () => import("../views/users.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/users/:id",
        name: "UsersDetail",
        component: () => import("../views/users-detail.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/users/:id/face-models-list",
        name: "FaceModelList",
        component: () => import("../views/FaceModelListView.vue"), // Lazy loading
        meta: { requiresAuth: true },
    },
    {
        path: "/profile",
        name: "Profile",
        component: () => import("../views/profile.vue"),
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard to check authentication
router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem("token");
    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: "Login" });
    } else {
        next();
    }
});

export default router;
