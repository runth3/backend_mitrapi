// resources/js/utils/deviceId.ts
import { v4 as uuidv4 } from "uuid";

/**
 * Menghasilkan atau mengambil device ID (UUID v4) yang unik untuk perangkat.
 * ID disimpan di localStorage untuk konsistensi antar sesi.
 * @returns {string} Device ID dalam format UUID v4
 */
export function getDeviceId(): string {
    let deviceId = localStorage.getItem("device_id");
    if (!deviceId) {
        deviceId = uuidv4();
        localStorage.setItem("device_id", deviceId);
    }
    return deviceId;
}
