# Checklist Kesiapan Rilis (Release Checklist Version 1)

Dokumen ini melacak kesiapan teknis, keamanan, integritas data, serta kelengkapan dokumentasi untuk pelepasan Version 1 Release Candidate pada Sistem Inventory.

---

## Matriks Checklist Version 1

### A. Source Code & Git Repository
- [x] Working tree Git dalam keadaan bersih (*clean working tree*).
- [x] Branch `main` terkonfirmasi sinkron dengan `origin/main` (Ahead: 0, Behind: 0).
- [x] Tidak ada artefak sementara, token sensitif, file backup, atau database dump di repository.

### B. Database & Inisialisasi Security
- [x] Migration skema database berjalan lancar (`php artisan migrate`).
- [x] Role dan Permission Seeder bersifat deterministik & konvergen (`RoleAndPermissionSeeder`).
- [x] Command interaktif pembuatan administrator awal tersedia dan teruji (`php artisan app:create-initial-admin`).
- [ ] Prosedur restore drill pada database uji terpisah berhasil dieksekusi (**Stage 10C**).
- [x] Rekonsiliasi saldo vs movement pada dataset verifikasi terbukti presisi 100% via BCMath (`ReleaseDatasetIntegrityTest`).

### C. Proteksi Keamanan & Kontrak Error
- [x] Rate limiting API aktif (`throttle:api` & `Limit::perMinute(60)`).
- [x] Exception handler mempertahankan header rate limit (`Content-Type`, `Retry-After`, `X-RateLimit-*`).
- [x] Matriks error HTTP (401, 403, 409, 422, 429) terverifikasi via automated feature tests.
- [x] Tidak ada password plaintext, secret key, atau private key pada `.env.example` maupun `VITE_*`.

### D. Pengujian Otomatis & Quality Gates
- [x] Focused tests (`RoleAndPermissionSeederTest`, `CreateInitialAdminCommandTest`, `ReleaseDatasetIntegrityTest`, `RateLimitingTest`, `BalanceMovementReconciliationTest`, `TransactionRollbackTest`) 100% PASS.
- [x] `ReleaseDatasetIntegrityTest` diverifikasi ulang **PASS 56/56** (104.948 assertion) pada database rehearsal terpisah (`InventorySystemRehearsal`) — 08-Agu-2026.
- [x] Parity discovery Artisan vs PHPUnit terkonfirmasi identik.
- [x] Executed full PHPUnit suite 100% PASS (0 failure, 0 error).
- [x] Format backend Pint lulus 0 issue (`./vendor/bin/pint --test`).
- [x] Frontend ESLint lulus 0 warning (`npm run lint`).
- [x] Production build Vite lulus tanpa error (`npm run build`).
- [x] Dependency audit terkonfirmasi bersih (0 dependency baru/berubah pada `composer.lock` & `package-lock.json`).

### E. Dokumentasi Resmi Rilis
- [x] `docs/INSTALLATION.md`
- [x] `docs/ENVIRONMENT.md`
- [x] `docs/DATABASE_SETUP.md`
- [x] `docs/MYSQL_BACKUP_RESTORE.md`
- [x] `docs/WAREHOUSE_USER_GUIDE.md`
- [x] `docs/UAT_VERSION_1.md` (Terstruktur; Siap dieksekusi pada Stage 10C)
- [x] `docs/PERFORMANCE_VERSION_1.md` (Terstruktur; Siap dieksekusi pada Stage 10C)
- [x] `docs/PRD_ACCEPTANCE_VERSION_1.md`
- [x] `docs/RELEASE_CHECKLIST_VERSION_1.md`
- [x] Navigasi `README.md` terupdate menyambungkan seluruh dokumen resmi.
