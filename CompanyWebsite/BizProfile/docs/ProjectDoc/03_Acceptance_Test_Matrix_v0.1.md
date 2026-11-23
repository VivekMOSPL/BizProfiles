# BizProfile — Phase One Acceptance & Test Matrix v0.1

**Statuses allowed:** NOT RUN / PASS / FAIL / BLOCKED. All entries below are **NOT RUN in the user's local deployment** unless explicitly marked otherwise. M0 patch unit syntax/smoke results are historical isolated-environment evidence, not deployment acceptance.

| ID | Phase | Test | Expected result | Status |
|---|---|---|---|---|
| M0-AT-01 | M0 | Verify backup & rollback | Source and DB snapshots recoverable | NOT RUN |
| M0-AT-02 | M0 | PHP config + pdo_mysql | Web runtime driver loaded, correct ini | NOT RUN |
| M0-AT-03 | M0 | Database connection | Connection and required tables pass | NOT RUN |
| M0-AT-04 | M0 | Homepage | `/` renders valid HTML (not blank/503) against DB | NOT RUN |
| M0-AT-05 | M0 | Admin login | `/admin/login` resolves view, login checked | NOT RUN |
| M0-AT-06 | M0 | Unknown route | Correct HTTP 404 and safe page | NOT RUN |
| M0-AT-07 | M0 | Failure mode | Missing DB produces safe 503 + private log | NOT RUN |
| M0-AT-08 | M0 | Source/config security | .env inaccessible from public HTTP / not packaged | NOT RUN |
| M0-AT-09 | M0 | Existing tests | Syntax fixed or exceptions recorded, tests executed | NOT RUN |
| M1-AT-01 | M1 | No provisioned email | Public owner claim is impossible | NOT RUN |
| M1-AT-02 | M1 | Wrong identifier | Neutral response; no enumeration, no access | NOT RUN |
| M1-AT-03 | M1 | Initial claim match | Matching provisioned email allows only provisional draft | NOT RUN |
| M1-AT-04 | M1 | OTP valid | Exactly one owner activated, secure session | NOT RUN |
| M1-AT-05 | M1 | OTP invalid/expired/reused | Denied; attempt/cooldown/limit enforced | NOT RUN |
| M1-AT-06 | M1 | Returning owner | OTP before private answers; exact step resume | NOT RUN |
| M1-AT-07 | M1 | Parallel claim race | One active owner, transaction safe | NOT RUN |
| M1-AT-08 | M1 | SMTP outage | Safe blocked state; no bypass; draft preserved | NOT RUN |
| M1-AT-09 | M1 | CLI email recovery | Audited owner recovery, invalidates old auth | NOT RUN |
| M1-AT-10 | M1 | Unauthorized admin | Access denied; owner/admin role enforced | NOT RUN |
| M1-AT-11 | M1 | No auto publication | Site remains unpublished after verification | NOT RUN |
| M1-AT-12 | M1 | Routes/regression | Existing public/admin URLs preserved | NOT RUN |
| M1-AT-13 | M1 | Session/CSRF | Rotation, flags, anti-CSRF and logout verified | NOT RUN |
| M1-AT-14 | M1 | Migration/rollback | Reversible where feasible; DB backups verified | NOT RUN |
| M1-AT-15 | M1 | Responsive/accessibility | Setup flows on desktop/mobile + keyboard | NOT RUN |

For each test record date/time, environment, source commit, input conditions, observed HTTP/status/output, screenshot/log reference (without secrets), tester, defect ID, and retest result. FAIL or BLOCKED prevents M1 sign-off on critical flows. A developer's self-report without objective evidence is not sufficient to mark integration PASS.
