# Components and Composables Guidelines

This document outlines the guidelines for creating and using components and composables in the project. The goal is to ensure consistency, reusability, and maintainability across the application.

## 1. Directory Structure

The project follows a structured directory layout to organize components, composables, views, and layouts:

-   **`resources/js/components/`**: Contains all reusable components.

    -   `BaseButton.vue`: Reusable button component.
    -   `BaseInput.vue`: Reusable input component.
    -   `BaseCard.vue`: Reusable card component for consistent card styling.
    -   `BaseAlert.vue`: Reusable alert component for error/success messages.
    -   `BaseSnackbar.vue`: Reusable snackbar component for notifications.
    -   `BaseAvatar.vue`: Reusable avatar component for user avatars.
    -   `ThemeSelector.vue`: Component for selecting themes (Normal, Night, Single Tone).
    -   `Sidebar.vue`: Reusable sidebar component for navigation.
    -   `LoginForm.vue`: Form component for login functionality.

-   **`resources/js/composables/`**: Contains all composables for shared logic.

    -   `useAuth.ts`: Handles authentication logic (login, logout, user role).
    -   `useAppTheme.ts`: Manages theme switching (Normal, Night, Single Tone).
    -   `useUser.ts`: Manages user data retrieval from localStorage.

-   **`resources/js/views/`**: Contains page-level components (Vue Router views).

    -   `login.vue`: Login page.
    -   `Dashboard.vue`: Dashboard page with fake stats.
    -   `news.vue`, `profile.vue`, `users.vue`, etc.: Other pages (to be implemented).

-   **`resources/js/layouts/`**: Contains layout components.
    -   `DefaultLayout.vue`: Default layout with app bar, sidebar, and main content area.

## 2. Base Components

### BaseButton

#### Deskripsi

`BaseButton` adalah komponen kustom berbasis `v-btn` dari Vuetify, dirancang untuk tombol yang konsisten di seluruh aplikasi. Mendukung ikon, ukuran, varian, dan sudut membulat yang dapat disesuaikan.

#### Props

| Prop          | Tipe                                                                 | Default      | Deskripsi                                                        |
| ------------- | -------------------------------------------------------------------- | ------------ | ---------------------------------------------------------------- |
| `color`       | `String`                                                             | `'primary'`  | Warna tombol (misalnya, `'primary'`, `'secondary'`, `'accent'`). |
| `size`        | `'x-small' \| 'small' \| 'default' \| 'large' \| 'x-large'`          | `'default'`  | Ukuran tombol (~24px hingga ~56px).                              |
| `variant`     | `'flat' \| 'elevated' \| 'tonal' \| 'outlined' \| 'text' \| 'plain'` | `'elevated'` | Varian gaya tombol.                                              |
| `disabled`    | `Boolean`                                                            | `false`      | Menonaktifkan tombol.                                            |
| `loading`     | `Boolean`                                                            | `false`      | Menampilkan indikator loading.                                   |
| `block`       | `Boolean`                                                            | `false`      | Membuat tombol memenuhi lebar kontainer.                         |
| `customClass` | `String`                                                             | `''`         | Kelas CSS tambahan (misalnya, Tailwind: `'mr-2'`).               |
| `prependIcon` | `String`                                                             | `undefined`  | Ikon di sisi kiri (misalnya, `'mdi-cog'`).                       |
| `appendIcon`  | `String`                                                             | `undefined`  | Ikon di sisi kanan (misalnya, `'mdi-star'`).                     |
| `rounded`     | `'0' \| 'sm' \| 'md' \| 'lg' \| 'xl' \| 'pill'`                      | `'lg'`       | Radius sudut tombol (~8px untuk `'lg'`, kapsul untuk `'pill'`).  |

#### Contoh Penggunaan

##### Tombol dengan Ikon dan Rounded

```vue
<BaseButton
    color="primary"
    prepend-icon="mdi-cog"
    rounded="lg"
    class="mr-2 mt-2"
    @click="handleClick"
>
  Settings
</BaseButton>
```

##### Tombol Block dengan Loading

```vue
<BaseButton
    color="secondary"
    :loading="isLoading"
    block
    rounded="pill"
    @click="submitForm"
>
  Submit
</BaseButton>
```

##### Tombol dengan Varian Outlined

```vue
<BaseButton
    size="small"
    color="primary"
    variant="outlined"
    rounded="lg"
    @click="changeTheme"
>
  Change Theme
</BaseButton>
```

#### Catatan Penting

-   **Ikon**: Gunakan ikon dari `@mdi/font` (misalnya, `'mdi-cog'`, `'mdi-star'`). Pastikan CDN `@mdi/font` dimuat di `spa.blade.php`.
-   **Tema**: Warna tombol (`color`) dipengaruhi oleh tema Vuetify (`normal`, `night`, `singleTone`). Pastikan `useAppTheme` digunakan untuk mengatur tema.
-   **Tailwind**: Gunakan `customClass` untuk kelas Tailwind seperti `'mr-2'` (margin-right 0.5rem) atau `'mt-4'` (margin-top 1rem).
-   **Ukuran**: Prop `size` memengaruhi tinggi tombol (`x-small` ~24px, `x-large` ~56px).

### BaseInput

#### Deskripsi

`BaseInput` adalah komponen kustom berbasis `v-text-field` dari Vuetify, dirancang untuk input teks yang konsisten dengan sudut membulat dan dukungan ikon.

#### Props

| Prop          | Tipe                                                          | Default      | Deskripsi                                                      |
| ------------- | ------------------------------------------------------------- | ------------ | -------------------------------------------------------------- |
| `modelValue`  | `String \| Number`                                            | `''`         | Nilai input (menggunakan `v-model`).                           |
| `color`       | `String`                                                      | `'primary'`  | Warna input (misalnya, `'primary'`, `'secondary'`).            |
| `label`       | `String`                                                      | `''`         | Label untuk input.                                             |
| `variant`     | `'filled' \| 'outlined' \| 'plain' \| 'underlined' \| 'solo'` | `'outlined'` | Varian gaya input.                                             |
| `disabled`    | `Boolean`                                                     | `false`      | Menonaktifkan input.                                           |
| `readonly`    | `Boolean`                                                     | `false`      | Membuat input hanya baca.                                      |
| `placeholder` | `String`                                                      | `''`         | Teks placeholder.                                              |
| `type`        | `String`                                                      | `'text'`     | Tipe input (misalnya, `'text'`, `'password'`).                 |
| `customClass` | `String`                                                      | `''`         | Kelas CSS tambahan (misalnya, Tailwind: `'mb-4'`).             |
| `rounded`     | `'0' \| 'sm' \| 'md' \| 'lg' \| 'xl' \| 'pill'`               | `'lg'`       | Radius sudut input (~8px untuk `'lg'`, kapsul untuk `'pill'`). |

#### Contoh Penggunaan

##### Input dengan Label dan Rounded

```vue
<BaseInput
    v-model="username"
    label="Username"
    color="primary"
    placeholder="Enter username"
    rounded="lg"
    class="mb-4"
/>
```

##### Input dengan Ikon dan Tipe Password

```vue
<BaseInput
    v-model="password"
    label="Password"
    color="primary"
    type="password"
    prepend-icon="mdi-lock"
    rounded="lg"
    class="mb-4"
/>
```

##### Input Readonly

```vue
<BaseInput
    v-model="email"
    label="Email"
    color="secondary"
    readonly
    rounded="pill"
    class="mb-4"
/>
```

#### Catatan Penting

-   **Ikon**: Gunakan `prepend-icon` atau `append-icon` (meskipun tidak eksplisit sebagai prop, didukung oleh `v-text-field`).
-   **Tema**: Warna input (`color`) dipengaruhi oleh tema Vuetify. Gunakan `useAppTheme` untuk mengatur tema.
-   **Tailwind**: Gunakan `customClass` untuk kelas Tailwind seperti `'mb-4'` (margin-bottom 1rem).
-   **Varian**: `variant="outlined"` memberikan border yang jelas dengan sudut membulat.

### BaseCard

#### Deskripsi

`BaseCard` adalah komponen kustom berbasis `v-card` dari Vuetify, dirancang untuk kartu yang konsisten di seluruh aplikasi. Mendukung styling tema yang dinamis.

#### Props

Menggunakan semua props dari `v-card` (diwarisi melalui `v-bind="$attrs"`). Tidak ada props tambahan yang didefinisikan secara eksplisit.

#### Contoh Penggunaan

##### Kartu dengan Judul dan Konten

```vue
<BaseCard>
    <v-card-title>User Information</v-card-title>
    <v-card-text>
        <p><strong>Name:</strong> John Doe</p>
        <p><strong>Email:</strong> john.doe@example.com</p>
    </v-card-text>
</BaseCard>
```

##### Kartu dengan Ikon dan Statistik

```vue
<BaseCard>
    <v-card-title>
        <v-icon left color="primary">mdi-account-group</v-icon>
        Total Users
    </v-card-title>
    <v-card-text>
        <h2 class="text-h4">1,245</h2>
        <p class="text-caption">Active users in the system</p>
    </v-card-text>
</BaseCard>
```

#### Catatan Penting

-   **Tema**: `BaseCard` secara otomatis menyesuaikan latar belakang berdasarkan tema (`normal`, `night`, `singleTone`).
    -   `normal` dan `singleTone`: `rgba(255, 255, 255, 0.9)`
    -   `night`: `rgba(46, 46, 46, 0.9)`
-   **Styling**: Gunakan Tailwind di dalam slot untuk menyesuaikan tata letak (misalnya, `'pa-5'` untuk padding).

### BaseAlert

#### Deskripsi

`BaseAlert` adalah komponen kustom berbasis `v-alert` dari Vuetify, dirancang untuk menampilkan pesan error, sukses, atau informasi dengan gaya konsisten.

#### Props

| Prop        | Tipe      | Default        | Deskripsi                                                   |
| ----------- | --------- | -------------- | ----------------------------------------------------------- |
| `type`      | `String`  | `'info'`       | Tipe alert (`'info'`, `'error'`, `'success'`, `'warning'`). |
| `textColor` | `String`  | `'on-surface'` | Warna teks alert.                                           |
| `density`   | `String`  | `'compact'`    | Density alert (`'default'`, `'comfortable'`, `'compact'`).  |
| `closable`  | `Boolean` | `false`        | Apakah alert bisa ditutup (menampilkan tombol close).       |

#### Contoh Penggunaan

##### Alert Error di Form

```vue
<BaseAlert
    v-if="error"
    type="error"
    text-color="on-surface"
    class="mt-4 text-body-2"
    density="compact"
    closable
    @click:close="error = null"
>
    {{ error }}
</BaseAlert>
```

##### Alert Sukses

```vue
<BaseAlert type="success" class="mt-4" density="compact">
    Data saved successfully!
</BaseAlert>
```

#### Catatan Penting

-   **Tema**: Warna alert (`type`) dipengaruhi oleh tema Vuetify. Pastikan `useAppTheme` digunakan untuk mengatur tema.
-   **Event**: Gunakan `@click:close` untuk menangani penutupan alert jika `closable` diaktifkan.

### BaseSnackbar

#### Deskripsi

`BaseSnackbar` adalah komponen kustom berbasis `v-snackbar` dari Vuetify, dirancang untuk menampilkan notifikasi sementara seperti pesan sukses atau error.

#### Props

| Prop         | Tipe      | Default          | Deskripsi                                                |
| ------------ | --------- | ---------------- | -------------------------------------------------------- |
| `modelValue` | `Boolean` | `false`          | Mengontrol visibilitas snackbar (menggunakan `v-model`). |
| `color`      | `String`  | `'info'`         | Warna snackbar (`'info'`, `'success'`, `'error'`).       |
| `timeout`    | `Number`  | `3000`           | Durasi tampilan dalam milidetik (default: 3 detik).      |
| `location`   | `String`  | `'bottom right'` | Posisi snackbar (`'top right'`, `'bottom left'`, dll.).  |
| `vertical`   | `Boolean` | `false`          | Apakah snackbar ditampilkan secara vertikal.             |
| `closable`   | `Boolean` | `true`           | Apakah snackbar memiliki tombol close.                   |

#### Contoh Penggunaan

##### Snackbar Selamat Datang

```vue
<BaseSnackbar
    v-model="showSnackbar"
    color="success"
    timeout="5000"
    location="top right"
    closable
>
    Welcome to Dashboard, {{ userName }}!
</BaseSnackbar>
```

##### Snackbar Error

```vue
<BaseSnackbar
    v-model="showError"
    color="error"
    timeout="4000"
    location="bottom left"
    closable
>
    Failed to save data!
</BaseSnackbar>
```

#### Catatan Penting

-   **v-model**: Gunakan `v-model` untuk mengontrol visibilitas snackbar (misalnya, `showSnackbar`).
-   **Timeout**: Atur `timeout` untuk menentukan berapa lama snackbar ditampilkan sebelum menghilang.
-   **Tema**: Warna snackbar (`color`) dipengaruhi oleh tema Vuetify.

### BaseAvatar

#### Deskripsi

`BaseAvatar` adalah komponen kustom berbasis `v-avatar` dari Vuetify, dirancang untuk menampilkan avatar user dengan gaya konsisten yang menyesuaikan tema.

#### Props

| Prop   | Tipe               | Default | Deskripsi                    |
| ------ | ------------------ | ------- | ---------------------------- |
| `size` | `String \| Number` | `'32'`  | Ukuran avatar (dalam pixel). |

#### Contoh Penggunaan

##### Avatar di App Bar

```vue
<v-btn icon>
    <BaseAvatar />
</v-btn>
```

##### Avatar dengan Ukuran Kustom

```vue
<BaseAvatar size="48" />
```

#### Catatan Penting

-   **Tema**: Warna avatar otomatis menyesuaikan tema (`grey-darken-3` untuk `night`, `grey-darken-1` untuk lainnya).
-   **Ikon Default**: Menggunakan ikon `mdi-account` secara default.

## 3. Reusable Components

### ThemeSelector

#### Deskripsi

`ThemeSelector` adalah komponen untuk memilih tema aplikasi (`normal`, `night`, `singleTone`). Digunakan di `DefaultLayout.vue` untuk mengganti tema melalui tombol.

#### Contoh Penggunaan

```vue
<v-list-item>
    <v-list-item-title>Theme</v-list-item-title>
    <v-list-item-subtitle>
        <ThemeSelector />
    </v-list-item-subtitle>
</v-list-item>
```

#### Catatan Penting

-   Menggunakan `useAppTheme` untuk mengatur tema.
-   Tombol menggunakan `BaseButton` dengan ikon (`mdi-palette`, `mdi-moon-waning-crescent`, `mdi-contrast-circle`).

### Sidebar

#### Deskripsi

`Sidebar` adalah komponen navigasi samping yang digunakan di `DefaultLayout.vue`. Menampilkan menu berdasarkan role user (`admin` atau `user`).

#### Props

| Prop         | Tipe      | Default | Deskripsi                                               |
| ------------ | --------- | ------- | ------------------------------------------------------- |
| `menuItems`  | `Array`   | -       | Daftar item menu (judul, ikon, path).                   |
| `modelValue` | `Boolean` | -       | Mengontrol visibilitas sidebar (menggunakan `v-model`). |

#### Contoh Penggunaan

```vue
<Sidebar :menu-items="activeMenuItems" v-model="drawer" />
```

#### Catatan Penting

-   Item menu dikonfigurasi di `config/menu.ts` (`adminMenuItems`, `userMenuItems`).
-   Menggunakan `v-navigation-drawer` dari Vuetify.

## 4. Composables

### useAuth

#### Deskripsi

`useAuth` adalah composable untuk mengelola autentikasi, termasuk login, logout, dan status role user (`isAdmin`).

#### Pengembalian

-   `login`: Fungsi untuk login (menerima `username` dan `password`).
-   `logout`: Fungsi untuk logout.
-   `error`: Ref untuk pesan error.
-   `loading`: Ref untuk status loading.
-   `isAdmin`: Ref untuk status admin.

#### Contoh Penggunaan

```typescript
const { login, logout, error, loading, isAdmin } = useAuth();

async function handleLogin() {
    try {
        await login(username.value, password.value);
    } catch (err) {
        console.error("Login failed:", err);
    }
}
```

### useAppTheme

#### Deskripsi

`useAppTheme` adalah composable untuk mengelola tema aplikasi (`normal`, `night`, `singleTone`).

#### Pengembalian

-   `currentTheme`: Ref untuk tema saat ini.
-   `setTheme`: Fungsi untuk mengatur tema.

#### Contoh Penggunaan

```typescript
const { currentTheme, setTheme } = useAppTheme();

function changeTheme() {
    setTheme("night");
}
```

### useUser

#### Deskripsi

`useUser` adalah composable untuk mengelola data user dari `localStorage`.

#### Pengembalian

-   `userName`: Ref untuk nama user.
-   `userEmail`: Ref untuk email user.
-   `userId`: Ref untuk ID user.
-   `loadUserData`: Fungsi untuk memuat ulang data user.

#### Contoh Penggunaan

```typescript
const { userName, userEmail } = useUser();

console.log(userName.value); // "John Doe"
console.log(userEmail.value); // "john.doe@example.com"
```

## 5. Catatan Umum

-   **Dependensi**:

    -   Pastikan Vuetify (`^3.8.2`) dan `@mdi/font@7.4.47` terinstal dan dikonfigurasi di `spa.blade.php`.
    -   Pastikan Vite (`^5.0.0`) dan `vite-plugin-vuetify` (`^2.0.4`) terinstal untuk build dan HMR.
    -   Tailwind CSS (`^3.4.0`) digunakan untuk styling tambahan.

-   **Proxy API**:

    -   Semua request API (`/api/*`) diarahkan ke backend Laravel (`http://localhost:8000`) melalui konfigurasi proxy di `vite.config.ts`:
        ```typescript
        server: {
            port: 3003,
            proxy: {
                "/api": {
                    target: "http://localhost:8000",
                    changeOrigin: true,
                    secure: false,
                },
            },
        }
        ```
    -   Endpoint autentikasi: `/api/auth/login`, `/api/auth/logout`.

-   **Testing**:

    -   Uji komponen di berbagai tema (`normal`, `night`, `singleTone`) untuk memastikan konsistensi warna.
    -   Uji responsivitas dengan kelas Tailwind seperti `'pa-5'`, `'mr-2'`, atau `'mt-4'`.

-   **Debugging**:

    -   Gunakan konsol browser (F12 > Console) untuk melacak error.
    -   Periksa Network tab untuk memastikan request API berhasil (status 200).
    -   Bersihkan cache Vite jika ada masalah HMR:
        ```bash
        rm -rf node_modules/.vite
        npm run dev
        ```

-   **Build**:

    -   Build aplikasi dengan:
        ```bash
        npm run build
        npm run preview
        ```
    -   Uji di `http://localhost:4173`.

-   **Struktur Proyek**:
    -   Simpan semua base components di `resources/js/components/` dengan awalan `Base` (misalnya, `BaseButton`, `BaseCard`).
    -   Simpan composables di `resources/js/composables/` dengan awalan `use` (misalnya, `useAuth`, `useUser`).
    -   Gunakan `v-bind="$attrs"` di base components untuk mewarisi props dari Vuetify.
