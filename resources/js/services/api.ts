// resources/js/api.ts
import axios from "axios";
import { getDeviceId } from "@/utils/deviceId";

const baseURL = (import.meta.env as any).VITE_API_BASE_URL as string;

const api = axios.create({
    baseURL,
    headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem("auth_token");
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    config.headers["X-Device-ID"] = getDeviceId();
    return config;
});

export default api;
