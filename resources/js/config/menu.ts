export interface MenuItem {
    title: string;
    icon: string;
    path: string;
}

export const adminMenuItems: MenuItem[] = [
    {
        title: "Dashboard",
        icon: "mdi-view-dashboard",
        path: "/dashboard",
    },
    { title: "News", icon: "mdi-newspaper", path: "/news" },
    { title: "Users", icon: "mdi-account-group", path: "/users" },
    { title: "Profile", icon: "mdi-account", path: "/profile" },
];

export const userMenuItems: MenuItem[] = [
    {
        title: "Dashboard",
        icon: "mdi-view-dashboard",
        path: "/dashboard",
    },
    { title: "News", icon: "mdi-newspaper", path: "/news" },
    { title: "Profile", icon: "mdi-account", path: "/profile" },
];
