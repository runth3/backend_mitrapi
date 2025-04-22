import { ref, onMounted } from "vue";

export function useUser() {
    const userName = ref<string>("Unknown User");
    const userEmail = ref<string>("No Email");
    const userId = ref<number | null>(null);

    function loadUserData() {
        const userData = localStorage.getItem("userData");
        if (userData) {
            const parsedUserData = JSON.parse(userData);
            userName.value = parsedUserData.name || "Unknown User";
            userEmail.value = parsedUserData.email || "No Email";
            userId.value = parsedUserData.id || null;
        }
    }

    onMounted(loadUserData);

    return {
        userName,
        userEmail,
        userId,
        loadUserData,
    };
}
