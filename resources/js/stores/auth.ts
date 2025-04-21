import { defineStore } from "pinia";
import { ref } from "vue";
import type { UserProfile, PegawaiData } from "@/types/auth";

export const useAuthStore = defineStore("auth", () => {
    const user = ref<UserProfile | null>(null);
    const pegawai = ref<{
        simpeg: PegawaiData;
        absen: PegawaiData;
        ekinerja: PegawaiData;
    } | null>(null);
    const token = ref<string | null>(null);

    function setAuthData(authData: {
        token: string;
        user: UserProfile;
        pegawai: {
            simpeg: PegawaiData;
            absen: PegawaiData;
            ekinerja: PegawaiData;
        };
    }) {
        token.value = authData.token;
        user.value = authData.user;
        pegawai.value = authData.pegawai;
    }

    function clearAuthData() {
        token.value = null;
        user.value = null;
        pegawai.value = null;
    }

    return { user, pegawai, token, setAuthData, clearAuthData };
});
