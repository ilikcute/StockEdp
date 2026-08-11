# StockEdp — Condensed Project History

Dokumen ini meringkas perjalanan proyek agar AI memahami bagaimana arsitektur dan keputusan saat ini terbentuk tanpa membaca seluruh chat.

## Fase 1–4 — Fondasi

Fondasi proyek dibangun sebagai Laravel + Vue 3 + MySQL dengan feature-first architecture.
Fokus awal:

- struktur modul;
- autentikasi;
- RBAC;
- master data;
- saldo/movement inventory;
- transaction safety;
- warehouse/location authorization;
- idempotency dan concurrency.

Fase 2 authentication/RBAC ditutup dengan automated tests dan lint/build PASS.
Fase 4 sempat menemukan blocker location gate, inactive entity validation, lock handling, concurrency, dan double-submit; semuanya diperbaiki sebelum closure.

## Fase 5 — Stock Transfer

Implemented:

- `stock_transfers`, `stock_transfer_items`;
- number `TRF-YYYYMM-XXXX`;
- Create/Update/Send/Receive/Cancel;
- warehouse authorization;
- active checks;
- atomic number generation;
- row locking;
- concurrency tests;
- maker/checker-compatible authorization.

V1 decision: tanpa partial receipt dan reversal transfer.

## Fase 6 — Stock Adjustment

Implemented:

- adjustment header/items;
- DRAFT → POSTED/CANCELED;
- reason/direction compatibility;
- Create/Update/Post/Cancel actions;
- maker-checker;
- permissions/policy;
- unique stock movement reference protection.

Frontend blocker pernah terjadi karena Resource belum mengirim `creator_id` dan abilities. Diperbaiki agar maker-checker UX tidak menebak policy sendiri.

## Fase 7 — Stock Opname

### 7A Freeze Infrastructure

Implemented `inventory_location_locks` dan `InventoryFreezeService`.
Lokasi frozen menolak mutation normal.
Global deterministic lock ordering ditetapkan untuk mengurangi deadlock.

### 7B Stock Opname Domain

Implemented:

- DRAFT → IN_PROGRESS → COUNTED → POSTED;
- CANCEL;
- REOPEN COUNTED → IN_PROGRESS;
- snapshot quantity;
- blind count;
- optimistic count version;
- immutable count/reopen logs;
- unexpected products;
- maker-checker creator/counter restriction;
- snapshot drift validation;
- `OPNAME_IN`/`OPNAME_OUT` reconciliation;
- freeze/unfreeze lifecycle.

## Fase 8 — Reporting

### 8A1

Implemented reports:

- inventory balances;
- low stock;
- stock card.

Key decisions:

- explicit allowed-location IDs into repository;
- ledger order tied to movement identity to preserve `quantity_before/after` math even with backdated document dates;
- report direction derived from exact balance delta;
- date filters use Asia/Jakarta and half-open intervals.

### 8A2

Added transaction reports:

- receipts;
- issues;
- transfers;
- adjustments;
- opnames.

`DecimalQuantity` hardened to decimal string + BCMath and reject runtime float paths.
Movement mapping uses class constants.
Lock/deadlock handling and concurrency runner were audited.

### 8B/8C Frontend + CSV

Reporting UI completed.
8 CSV exports implemented as synchronous streamed UTF-8 BOM responses with formula-injection protection, deterministic streaming, decimal passthrough, and location scoping.

## Fase 9 — Hardening

Security/integrity hardening included:

- rollback testing;
- movement/balance reconciliation;
- sensitive data audit;
- `.env` exclusion;
- no secret in VITE variables;
- rate limiting;
- HTTP error contracts.

Fase 9 closed.

## Fase 10B — Release Foundation

Added:

- deterministic RoleAndPermissionSeeder;
- interactive initial admin command;
- installation/env/database docs;
- backup/restore docs;
- warehouse user guide;
- ReleaseVerificationSeeder;
- release acceptance/checklist docs.

Automated quality gates closed clean.

## Fase 10C-1 — Recoverability

Performed actual backup/restore rehearsal.

Accepted evidence included:

- source `stockedp_release_rehearsal`;
- restore `stockedp_release_restore_test`;
- secure interactive password usage;
- checksum;
- source/restore parity across 22 data/pivot tables;
- RBAC parity;
- freeze-lock parity;
- ledger integrity.

## Fase 10C-2 — Canonical UAT

12/12 operational scenarios passed.

Critical lessons:

- Maker-checker cannot be proven merely by a 403 from a role that lacks POST permission. A capable creator/counter must be rejected while a distinct capable checker succeeds.
- Location scope/IDOR was explicitly verified.
- Blind count was verified via raw API response.
- 429 behavior preserved Retry-After without unwanted logout/form reset.

## Fase 10C-3 — Performance & Production-Like Verification

Initial walkthrough was rejected because:

- global max was incorrectly reported as 88.29ms while actual table max was 267.55ms;
- “human” timing values were implausibly tiny backend-style timings;
- required search/filter/detail matrix was incomplete;
- production-like rehearsal was read-only/incomplete;
- regression/discovery evidence was vague.

After correction:

- 17 base + 11 supplemental operations passed;
- global API max 267.55ms;
- 10 real browser workflows all <60 sec, max 12.637 sec;
- full write rehearsal passed;
- concurrency passed;
- exact normalized test discovery 371/371;
- full regression 371/371, 106,228 assertions;
- focused reporting/CSV/reconciliation/release-integrity tests passed.

## Fase 10C-4 — Release Candidate Acceptance

Combined accepted 10C-1/2/3 evidence.
Release Candidate accepted at:

```text
64e9e3c89bd0cff2d5fed7c60b94605054f2ecc7
```

10D intentionally remained separate.

## Fase 10D — Independent Final Release Audit

An early docs-only commit declared Stable too soon. It was rejected because no fresh independent 10D evidence was bound.

Correction performed:

- fresh source architecture audit;
- decimal precision audit;
- transaction/locking audit;
- RBAC/maker-checker;
- location IDOR;
- opname freeze/blind count;
- secret/artifact/dependency audit;
- fresh normalized discovery;
- fresh full regression;
- critical focused suites;
- Pint/ESLint/Vite/Optimize;
- APP_DEBUG=false smoke;
- pre/post source hash parity.

Final evidence bound at:

```text
674dbf2e5b4d047fd8a67fee91a04f8caeb2b613
```

Version 1 then became legitimately:

```text
STABLE
RELEASE READY
NOT DEPLOYED
NO TAG
NO GITHUB RELEASE
```

## Post-Release-Readiness Database Clarification

After V1 certification, database roles were clarified:

```text
stockedp                       = operational/release database
stockedp_release_uat           = UAT/performance/rehearsal only
stockedp_release_rehearsal     = recoverability source only
stockedp_release_restore_test  = restore target only
```

Important discovery:

- `migrate:fresh` does not create MySQL database schema;
- database `stockedp` must be created first;
- `.env.example` historically used `InventorySystem`, inconsistent with release docs using `stockedp`;
- `DatabaseSeeder` historically creates an admin factory user with test default password, so `migrate:fresh --seed` is not release bootstrap;
- release bootstrap uses `migrate`, `RoleAndPermissionSeeder`, then `app:create-initial-admin`.

## Fase 11A — Master Data Bulk Import

Import massal produk, kategori, satuan, dan lokasi persediaan via CSV template validation, preview, & commit transaction.

## Fase 12A — Operational Inventory Dashboard & Computed Alert Center

Read-only Operational Dashboard & Computed Alert Center.
- `GET /api/v1/dashboard` read-only (`delta = 0`).
- Real-time computed alerts (zero notification tables).
- RBAC `dashboard.view` & location scoping via `$user->getAllowedLocationIds()`.
- Inventory Health, Operational Queue, Recent Activity (Max 10), dan Top Movement Products.
- 100% Low Stock Count Parity dengan canonical Low Stock Report.
