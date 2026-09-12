# StockEdp — AI Working Workflow

## 1. Sebelum Menulis Kode

Baca minimal:

```text
skills/stockedp/SKILL.md
AGENTS.md
```

Kemudian baca context sesuai task:

```text
database   → DATABASE.md
backend    → BACKEND.md
frontend   → FRONTEND.md
release    → RELEASE.md
history    → HISTORY.md
```

Jika task mengubah keputusan domain/arsitektur, baca `DECISIONS.md` dan `ARCHITECTURE.md`.

## 2. Baseline Wajib

Sebelum task besar/audit:

```bash
git fetch origin
git branch --show-current
git rev-parse HEAD
git rev-parse origin/main
git status --short
```

Catat SHA baseline.

Jangan mencampur evidence dari SHA lama dengan source yang sudah berubah.

## 3. Scope Discipline

Sebelum edit, definisikan:

- masalah;
- file yang boleh disentuh;
- invariant yang harus dipertahankan;
- test yang harus PASS;
- out-of-scope.

Rules:

- no unrelated refactor;
- no dependency baru tanpa izin;
- no test weakening;
- match existing gold-standard feature;
- jangan invent endpoint/schema/permission jika source bisa diperiksa.

## 4. Cara Implementasi Backend

Untuk mutation inventory:

```text
Request validation
→ backend authorization
→ Action
→ DB transaction
→ deterministic lock
→ business validation
→ movement
→ balance
→ state transition
→ commit
```

Tambahkan/ubah tests untuk:

- happy path;
- authorization;
- state transition invalid;
- rollback;
- idempotency/double submit;
- concurrency bila applicable;
- precision edge cases.

## 5. Cara Implementasi Frontend

Flow:

```text
Page/Component
→ Store/Composable
→ Feature API
→ shared apiClient
```

Selalu tangani:

```text
loading
empty
success
validation
403
409
429
network error
```

Gunakan backend abilities untuk UX action guards.

## 6. Decimal Review Checklist

Jika task menyentuh quantity:

- DB tetap `DECIMAL(14,4)`;
- PHP tidak cast float;
- gunakan BCMath/string;
- frontend tidak hitung authoritative quantity dengan JS Number;
- negative zero canonical;
- test fractional dan boundary values.

## 7. Database Change Checklist

Jika migration baru:

- forward migration, jangan edit migration release lama kecuali explicit pre-release context;
- FK/index/check constraint dievaluasi;
- migration fresh + upgrade path diperiksa;
- test MySQL behavior bila fitur bergantung pada lock/generated column/check constraint;
- tidak menjalankan `migrate:fresh` pada production.

## 8. Quality Gate

Default serial gate:

```bash
php artisan optimize:clear
php artisan test
php vendor/bin/pint --test
node node_modules/eslint/bin/eslint.js resources/js --max-warnings 0
npm run build
php artisan optimize
```

Untuk perubahan kecil, focused tests boleh dijalankan dulu, tetapi final source release-impacting harus mempunyai regression yang memadai.

Jangan menjalankan beberapa full test runner bersamaan terhadap DB MySQL yang sama.

## 9. Audit Walkthrough dari Coding Agent

Jika user menempel walkthrough:

### Jangan lakukan

- percaya summary `PASS` tanpa cek;
- menerima raw line count sebagai test discovery parity;
- menerima timing backend microseconds sebagai human workflow timing;
- menerima authorization 403 yang sebenarnya karena role tidak punya permission sebagai bukti maker-checker;
- menerima docs-only stable declaration tanpa evidence audit.

### Lakukan

1. cek latest remote commit;
2. compare dengan accepted baseline;
3. klasifikasikan changed files;
4. bila source berubah, inspect source dan regression binding;
5. fetch authoritative docs terkait;
6. cari kontradiksi angka/status;
7. hitung findings severity;
8. tetapkan PASS/HOLD/CLOSED/READY.

## 10. Severity Guidance

Critical:

- stock corruption;
- authorization/IDOR bypass;
- real secret leak;
- unrecoverable release database;
- negative stock/concurrency race yang merusak integritas.

High:

- core transaction tidak valid;
- maker-checker bypass;
- blind-count leak;
- full regression gagal;
- mandatory release gate tidak memiliki evidence.

Medium:

- kontradiksi release docs;
- performance/acceptance evidence tidak lengkap;
- operational defect penting tetapi bukan corrupt/security bypass.

Low:

- wording/cosmetic docs issue non-blocking.

## 11. Gate Language

Gunakan status eksplisit:

```text
PASS
HOLD
CLOSED
READY FOR <NEXT STAGE>
```

Untuk gate release:

```text
Critical: X
High: X
Medium: X
Low: X
```

Jangan menyatakan stable/release-ready bila mandatory Critical/High/Medium belum 0.

## 12. Dokumentasi Setelah Perubahan

Update hanya docs yang terdampak.

- keputusan baru → `DECISIONS.md`;
- pekerjaan/fase → `TASKS.md`;
- API/arsitektur → docs terkait;
- context skill ini → update jika ada keputusan besar yang harus diingat AI berikutnya.

Jangan memasukkan log mentah besar, dump DB, credential, atau temporary benchmark artifacts ke git.

## 13. Commit Policy

Commit harus scoped dan mudah diaudit.

Contoh:

```text
fix: enforce stock transfer location lock ordering
test: cover adjustment maker-checker concurrency
docs: update release database bootstrap guidance
```

Jika task hanya docs, jangan menyisipkan source mutation di commit yang sama.

## 14. Definition of Done

Task dianggap selesai jika:

- sesuai request user;
- mengikuti architecture/domain rules;
- authorization benar;
- inventory integrity terjaga;
- tests relevan PASS;
- lint/build PASS sesuai scope;
- tidak ada unrelated changes;
- docs keputusan/context diperbarui bila perlu;
- repository state/evidence dijelaskan dengan jujur.
