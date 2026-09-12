# Panduan Konfigurasi Environment (`.env`)

Dokumen ini mendokumentasikan setiap variable konfigurasi lingkungan yang digunakan oleh Sistem Inventory Version 1.

---

## 1. Parameter Aplikasi Utama

| Variable | Fungsi | Rekomendasi Production |
| --- | --- | --- |
| `APP_NAME` | Nama aplikasi | `"InventorySystem"` |
| `APP_ENV` | Lingkungan aplikasi (`local`, `testing`, `production`) | `production` |
| `APP_KEY` | Key enkripsi aplikasi (dihasilkan via `key:generate`) | Key unik 32-karakter |
| `APP_DEBUG` | Mode debug error trace (`true` / `false`) | `false` |
| `APP_TIMEZONE` | Zone waktu aplikasi | `Asia/Jakarta` |
| `APP_URL` | URL dasar akses backend | `http://localhost:8000` |
| `APP_LOCALE` | Bahasa utama aplikasi | `id` |

---

## 2. Parameter Database MySQL

| Variable | Fungsi | Contoh Placeholder |
| --- | --- | --- |
| `DB_CONNECTION` | Driver koneksi database | `mysql` |
| `DB_HOST` | Host server database | `127.0.0.1` |
| `DB_PORT` | Port server database | `3306` |
| `DB_DATABASE` | Nama database MySQL | `stockedp` |
| `DB_USERNAME` | Username MySQL | `<DB_USER>` |
| `DB_PASSWORD` | Password MySQL | `<DB_PASSWORD>` |

---

## 3. Parameter Cache, Session, dan Queue

| Variable | Fungsi | Rekomendasi Production |
| --- | --- | --- |
| `SESSION_DRIVER` | Storage penyimpanan session | `database` |
| `CACHE_STORE` | Store caching aplikasi | `database` |
| `QUEUE_CONNECTION` | Driver antrean job | `database` |
| `FILESYSTEM_DISK` | Disk penyimpanan file lokal | `local` |
| `LOG_CHANNEL` | Channel logging | `stack` |
| `LOG_LEVEL` | Level log (`debug`, `info`, `warning`, `error`) | `info` |

---

## 4. Parameter Frontend / Vite

| Variable | Fungsi | Contoh Value |
| --- | --- | --- |
| `VITE_APP_NAME` | Nama aplikasi di frontend | `"${APP_NAME}"` |
| `VITE_API_BASE_URL` | Base URL REST API | `http://localhost:8000/api/v1` |
| `VITE_API_TIMEOUT_MS` | Timeout request HTTP (ms) | `10000` |

---

## 5. Security & CORS

| Variable | Fungsi | Contoh Value |
| --- | --- | --- |
| `CORS_ALLOWED_ORIGINS` | Origin frontend yang diizinkan | `http://localhost:5173,http://localhost:8000` |
| `SANCTUM_STATEFUL_DOMAINS` | Domain stateful cookie Sanctum | `localhost:5173,127.0.0.1:5173` |

---

> **CATATAN KEAMANAN**:  
> - Dilarang memasukkan password, secret key, atau private key ke dalam `.env.example`.  
> - Dilarang menaruh API key sensitif pada variable ber-prefix `VITE_` karena akan ikut terkompilasi ke dalam browser bundle.
