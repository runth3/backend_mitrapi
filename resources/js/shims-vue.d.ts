declare module "*.vue" {
    import { DefineComponent } from "vue";
    const component: DefineComponent<{}, {}, any>;
    export default component;
}

interface ImportMetaEnv {
    readonly VITE_API_BASE_URL: string;
    // Tambahkan deklarasi untuk variabel lingkungan VITE_ lainnya di sini
    [key: string]: any;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
