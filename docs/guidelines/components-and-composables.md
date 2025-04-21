# Components and Composables Guidelines

## BaseButton

### Deskripsi

`BaseButton` adalah komponen kustom berbasis `v-btn` dari Vuetify, dirancang untuk tombol yang konsisten di seluruh aplikasi. Mendukung ikon, ukuran, varian, dan sudut membulat yang dapat disesuaikan.

### Props

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

### Contoh Penggunaan

#### Tombol dengan Ikon dan Rounded

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

#### Tombol Block dengan Loading

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

#### Tombol dengan Varian Outlined

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

### Catatan Penting

-   **Ikon**: Gunakan ikon dari `@mdi/font` (misalnya, `'mdi-cog'`, `'mdi-star'`). Pastikan CDN `@mdi/font` dimuat di `spa.blade.php`.
-   **Tema**: Warna tombol (`color`) dipengaruhi oleh tema Vuetify (`normal`, `night`, `singleTone`). Pastikan `useAppTheme` digunakan untuk mengatur tema.
-   **Tailwind**: Gunakan `customClass` untuk kelas Tailwind seperti `'mr-2'` (margin-right 0.5rem) atau `'mt-4'` (margin-top 1rem).
-   **Ukuran**: Prop `size` memengaruhi tinggi tombol (`x-small` ~24px, `x-large` ~56px).

## BaseInput

### Deskripsi

`BaseInput` adalah komponen kustom berbasis `v-text-field` dari Vuetify, dirancang untuk input teks yang konsisten dengan sudut membulat dan dukungan ikon.

### Props

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

### Contoh Penggunaan

#### Input dengan Label dan Rounded

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

#### Input dengan Ikon dan Tipe Password

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

#### Input Readonly

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

### Catatan Penting

-   **Ikon**: Gunakan `prepend-icon` atau `append-icon` (meskipun tidak eksplisit sebagai prop, didukung oleh `v-text-field`).
-   **Tema**: Warna input (`color`) dipengaruhi oleh tema Vuetify. Gunakan `useAppTheme` untuk mengatur tema.
-   **Tailwind**: Gunakan `customClass` untuk kelas Tailwind seperti `'mb-4'` (margin-bottom 1rem).
-   **Varian**: `variant="outlined"` memberikan border yang jelas dengan sudut membulat.

## Catatan Umum

-   **Dependensi**: Pastikan Vuetify (`^3.8.2`) dan `@mdi/font@7.4.47` terinstal dan dikonfigurasi di `spa.blade.php`.
-   **Testing**: Uji komponen di berbagai tema (`normal`, `night`, `singleTone`) untuk memastikan konsistensi warna.
-   **Responsivitas**: Gunakan kelas Tailwind seperti `'pa-5'`, `'mr-2'`, atau `'mt-4'` untuk tata letak responsif.
