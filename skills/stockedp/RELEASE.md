# StockEdp — Release & Audit Context

## 1. Final Version 1 Status

Authoritative certified Version 1 SHA:

```text
674dbf2e5b4d047fd8a67fee91a04f8caeb2b613
```

Final status:

```text
FASE 10D — PASS WITH CLEAN INDEPENDENT FINAL RELEASE AUDIT
FASE 10D — CLOSED
VERSION 1 — STABLE
VERSION 1 — RELEASE READY
VERSION 1 — NOT DEPLOYED
VERSION 1 — NO TAG CREATED
VERSION 1 — NO GITHUB RELEASE CREATED
```

## 2. Release Chain

```text
Fase 10B   Release Foundation                  PASS/CLOSED
Fase 10C-1 Secure Backup & Recoverability      PASS/CLOSED
Fase 10C-2 Canonical Operational UAT           PASS/CLOSED
Fase 10C-3 Performance/Timing/Concurrency      PASS/CLOSED
Fase 10C-4 Release Candidate Acceptance        PASS/CLOSED
Fase 10D   Independent Final Release Audit     PASS/CLOSED
```

## 3. Important SHAs

```text
10C-1 recoverability evidence
1085c25b706b3508ea573fc88b90b8cb2d34e7dc

10C-2 canonical UAT acceptance
4ce888c7e1ed326feaa936cfd40567bd58d51111

reporting source correction ancestor
99402d3a743bcf741ceaf79556dd61d6245bffe1

10C-3 operational performance closure
926816c8d9844668ba7cc79beaf8fd84fd57a523

10C-3 final regression binding
7a465172c016e6b6ca1ed5a8b85cbb7e5a493bc2

10C release candidate acceptance
64e9e3c89bd0cff2d5fed7c60b94605054f2ecc7

initial 10D stable declaration (declaration only)
c2c800269a350ee0e901e44834c2f35688b94fb4

10D independent final evidence / Version 1 authoritative
674dbf2e5b4d047fd8a67fee91a04f8caeb2b613
```

## 4. 10C-1 Recoverability Accepted Evidence

Source rehearsal DB:

```text
stockedp_release_rehearsal
```

Restore target:

```text
stockedp_release_restore_test
```

Accepted:

- migrations clean;
- role/permission bootstrap;
- secure interactive initial admin;
- release verification dataset;
- idempotency;
- mysqldump exit 0;
- restore exit 0;
- checksum recorded;
- parity 22 tables/pivots;
- RBAC parity;
- inventory location lock parity;
- ledger integrity.

## 5. 10C-2 Canonical UAT

12/12 canonical scenarios PASS:

```text
UAT-01 Login / RBAC
UAT-02 Master Data
UAT-03 Receipt
UAT-04 Issue
UAT-05 Transfer
UAT-06 Adjustment Maker-Checker
UAT-07 Stock Opname
UAT-08 Reports
UAT-09 CSV 8 reports
UAT-10 Movement Traceability
UAT-11 Rate Limiting / 429
UAT-12 Responsive UI
```

Special evidence:

- Adjustment: capable creator self-post → 403 maker-checker violation; distinct checker posts.
- Opname: creator cannot post; counter participant cannot post; distinct checker posts.
- Blind count hides snapshot/variance.
- Surplus → `OPNAME_IN`, shortage → `OPNAME_OUT`.
- All 8 report CSV exports PASS.
- Location-scope/IDOR direct access rejected.
- Ledger reconciliation exact with BCMath.

## 6. 10C-3 Performance

Rehearsal DB:

```text
stockedp_release_uat
```

Dataset around final benchmark:

```text
1,004 products
5,006 balances
10,015 movements
```

Environment:

```text
APP_ENV=local
APP_DEBUG=false
Laravel optimized caches
Production Vite build
```

Performance matrix:

```text
17 base API operations
11 supplemental search/filter/detail operations
28/28 PASS
```

Global maximum accepted:

```text
267.55 ms <= 2000 ms
```

Human operational timing:

```text
10/10 workflows < 60 sec
max = 12.637 sec
```

Continuous production-like write rehearsal:

```text
Cold restart
→ Login
→ Balances
→ Receipt POST
→ Issue POST
→ Transfer SEND/RECEIVE
→ Report/filter
→ CSV download
→ Logout
```

Accepted:

```text
console errors = 0
unexpected network 5xx = 0
unexplained log errors = 0
negative balances = 0
ledger reconciliation = PASS
```

## 7. Regression Baseline V1

Final independent audit:

```text
Normalized Artisan tests : 371
Normalized PHPUnit tests : 371
Missing / Extra          : 0 / 0
Exact set parity          : PASS

Full tests                : 371/371 PASS
Assertions                : 106,228
Failures                  : 0
Errors                    : 0
Risky                     : 0
Skipped                   : 0
Exit code                 : 0
```

Focused:

```text
Concurrency              16 / 34 assertions
Reporting                69 / 507
CsvExport                26 / 249
ReleaseDatasetIntegrity  56 / 104,948
Reconciliation           5 / 55,028
```

Counts are historical baselines, not constants. New legitimate tests may increase them.

## 8. Final Independent 10D Audit

Accepted independent checks:

- architecture layering;
- no business API bypass from `.vue`;
- decimal precision / no float inventory arithmetic;
- transaction & pessimistic locking;
- movement invariants;
- RBAC / maker-checker;
- location scope / IDOR;
- opname freeze / blind count;
- real secrets = 0;
- unintended `.sql/.dump/.bak` = 0;
- unauthorized dependency mutation = 0;
- fresh discovery/full regression;
- focused critical regression;
- Pint/ESLint/Vite/Optimize;
- APP_DEBUG=false smoke;
- pre/post source hash parity.

## 9. Audit Lesson: Evidence > Labels

Important historical lesson:

A docs commit once declared `VERSION 1 STABLE` before independent 10D evidence existed. That declaration was rejected until fresh evidence was bound in commit `674dbf2...`.

Therefore AI must:

- treat PASS/CLOSED text as claim;
- verify commit/diff/source/evidence;
- distinguish documentation-only commit from source mutation;
- bind regression to the correct SHA;
- never inherit an old test run across unexplained source changes.

## 10. Release vs Deployment

`RELEASE READY` does not mean deployed.

Before actual deployment, separately plan:

- production DB creation/configuration;
- secret management;
- migration/bootstrap;
- backup baseline;
- web server/runtime;
- queue/scheduler if used;
- HTTPS/domain/CORS/Sanctum;
- monitoring/logging;
- rollback plan;
- optional Git tag/GitHub Release.

Do not claim deployment occurred without explicit execution/evidence.
