import { createRouter, createWebHistory } from "vue-router";

// Lazy load components
const Login = () => import("@/views/login.vue");
const Dashboard = () => import("@/views/dashboard.vue");
const FaceModelListView = () => import("@/views/FaceModelListView.vue");
const CreateNews = () => import("@/views/create-news.vue");
const NewsDetail = () => import("@/views/news-detail.vue");
const News = () => import("@/views/news.vue");
const Profile = () => import("@/views/profile.vue");
const UsersDetail = () => import("@/views/users-detail.vue");
const Users = () => import("@/views/users.vue");

const routes = [
    { path: "/", name: "login", component: Login },
    { path: "/dashboard", name: "dashboard", component: Dashboard },
    {
        path: "/face-model-list/:id",
        name: "faceModelList",
        component: FaceModelListView,
    },
    { path: "/news/create", name: "createNews", component: CreateNews },
    { path: "/news/:id", name: "newsDetail", component: NewsDetail },
    { path: "/news", name: "news", component: News },
    { path: "/profile", name: "profile", component: Profile },
    { path: "/users/:id", name: "usersDetail", component: UsersDetail },
    { path: "/users", name: "users", component: Users },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard untuk autentikasi
router.beforeEach((to, from, next) => {
    const isAuthenticated = !!localStorage.getItem("auth_token");
    const isAdmin = localStorage.getItem("is_admin") === "1";
    const requiresAuth = to.name !== "login";
    const adminRoutes = ["users", "usersDetail", "faceModelList"];

    console.log("Navigation guard:", {
        to: to.name,
        from: from.name,
        isAuthenticated,
        isAdmin,
        requiresAuth,
    });

    if (requiresAuth && !isAuthenticated) {
        console.log("Not authenticated, redirecting to login");
        next({ name: "login" });
    } else if (to.name === "login" && isAuthenticated) {
        console.log("Already authenticated, redirecting to dashboard");
        next({ name: "dashboard" });
    } else if (adminRoutes.includes(to.name as string) && !isAdmin) {
        console.log("Not admin, redirecting to dashboard");
        next({ name: "dashboard" });
    } else {
        console.log("Navigation allowed, proceeding");
        next();
    }
});

export default router;
