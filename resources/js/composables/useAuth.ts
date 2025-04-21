import { ref } from "vue";
import { useRouter } from "vue-router";

export function useAuth() {
    const router = useRouter();
    const loading = ref(false);
    const error = ref<string | null>(null);
    const isAdmin = ref(false);

    async function login(username: string, password: string) {
        loading.value = true;
        error.value = null;
        try {
            console.log("Attempting to login with username:", username);
            const response = await fetch("/api/auth/login", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({ username, password }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || "Login failed");
            }

            const data = await response.json();
            console.log("Login response:", data);

            if (data.status !== "success") {
                throw new Error(data.message || "Login failed");
            }

            // Simpan token dan user data
            localStorage.setItem("auth_token", data.data.access_token);
            localStorage.setItem("userData", JSON.stringify(data.data.user));

            // Simpan is_admin ke localStorage
            localStorage.setItem(
                "is_admin",
                data.data.user.is_admin.toString()
            );
            isAdmin.value = data.data.user.is_admin === 1;

            console.log("Redirecting to /dashboard");
            await router.push("/dashboard");
            console.log("Redirect completed");
        } catch (err: any) {
            error.value = err.message || "Invalid username or password";
            console.error("Login error:", err);
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            const token = localStorage.getItem("auth_token");
            if (token) {
                await fetch("/api/auth/logout", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        Authorization: `Bearer ${token}`,
                    },
                });
            }
        } catch (err) {
            console.error("Logout API failed:", err);
        } finally {
            localStorage.removeItem("auth_token");
            localStorage.removeItem("userData");
            localStorage.removeItem("is_admin");
            isAdmin.value = false;
            await router.push("/login");
        }
    }

    // Membaca is_admin dari localStorage saat aplikasi dimuat
    function loadAuthState() {
        const storedIsAdmin = localStorage.getItem("is_admin");
        isAdmin.value = storedIsAdmin === "1";
    }

    loadAuthState();

    return { login, logout, error, loading, isAdmin };
}
