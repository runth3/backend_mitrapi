export interface ApiResponse<T> {
    status: "success" | "error";
    data: T;
    error: string | null;
    last_updated: string;
    message: string;
}

export interface LoginResponse {
    access_token: string;
    token_type: "Bearer";
    refresh_token: string;
    expires_at: string;
}

export interface UserProfile {
    name: string;
    username: string;
    email: string;
    phone: string | null;
    dob: string | null;
    address: string | null;
    created_at: string;
    updated_at: string;
}

export interface PegawaiData {
    id_pegawai: string;
    nip: string;
    nama_lengkap: string;
    gelar: string;
    tempat_lahir: string;
    tgl_lahir: string;
    jenis_kelamin: string;
    id_pangkat: number;
    id_instansi: string | number;
    id_unit_kerja: string;
    id_sub_unit_kerja: string;
    id_jabatan: number;
    tmt_jabatan: string;
    id_eselon: number;
    alamat: string;
    no_telp: string;
    office: {
        id_instansi: string | number;
        nama_instansi: string;
    };
}

export interface ProfileResponse {
    user: UserProfile;
    dataPegawaiSimpeg: PegawaiData;
    dataPegawaiAbsen: PegawaiData;
    dataPegawaiEkinerja: PegawaiData;
}
