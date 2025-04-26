// resources/js/api.ts
import axios from "axios";
import { getDeviceId } from "@/utils/deviceId";

const baseURL = import.meta.env.VITE_API_BASE_URL || "/api";

const api = axios.create({
    baseURL,
    headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-Device-ID": getDeviceId(), // Set header default
    },
});

api.interceptors.request.use((config) => {
    // Tambahkan Authorization header jika token ada
    const token = localStorage.getItem("auth_token");
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    // Pastikan X-Device-ID selalu ada (override jika perlu)
    config.headers["X-Device-ID"] = getDeviceId();
    return config;
});

export default api;
