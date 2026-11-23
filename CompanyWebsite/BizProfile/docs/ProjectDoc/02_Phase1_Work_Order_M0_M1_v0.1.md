# BizProfile — First Development Phase Work Order (M0 Gate Closure → M1)

**Document:** M1-WO-v0.1 | **Status:** READY FOR REVIEW; NOT AN IMPLEMENTATION AUTHORIZATION  
**Governing reference:** Master SRS v0.4 DRAFT and source-grounded M0 report v0.1.

## 0. Execute M0 local closure first (no feature expansion)

- Backup BizProfile source **and** MySQL database; record timestamp, paths and hashes.
- Locate active PHP interpreter and web document root; check `php -v`, `php --ini`, `php -m` for `pdo_mysql`; inspect actual serving runtime.
- Apply M0 Recovery Patch v0.1 only to verified working copy, retaining originals/rollback.
- Confirm `.env` is outside publicly served files; validate environment keys without printing values.
- Verify MySQL connection, schema presence, root `/`, unknown path 404, `/admin/login`, and admin view resolution.
- Restore absent admin view PHP templates from trusted history **or** document minimum UI restoration as a separately approved M0 subtask; do not invent new authentication functionality in M0.
- Repair or quarantine broken legacy test files under a separately recorded M0 maintenance change; run available syntax and smoke tests.
- Record logs, HTTP status, screenshots, tests, defects, backup/rollback outcomes. No 'PASS' based only on source inspection.

**M0 exit:** Root renders with live DB, admin login renders, no fatal/blank page, 404/error handling proper, security of config verified, tests/evidence attached. Otherwise report BLOCKED, do not claim M0 complete.

## 1. M1 functional goal

On a new, initialized single-business deployment, an unauthorized visitor sees a polished setup-pending page; a deployer-provisioned authorized owner can enter identification, begin a limited draft, verify email ownership using OTP, return safely to resume and reach a restricted admin landing page. Existing published-site routes continue to work. No brand/industry/theme/content generation yet.

## 2. Strict in-scope deliverables

1. Configurable server-side install/onboarding/publish state reader, route pre-dispatch gate, safe error states and setup-only robots noindex.
2. Protected CLI provision/inspect/rotate owner email; do not expose provisioning via public HTTP.
3. Premium accessible setup welcome and identification forms (name, business, email, mobile); neutral account existence responses.
4. Claim service that matches provisioned email without trusting browser-only state, creates provisional data without owner privileges, guarantees singleton owner activation.
5. OTP send/verify/retry/expiry/attempt tracking using secure keyed hashing, session binding, IP/account throttling, and audited consumption. Mail transport configurable; no bypass on failure.
6. Secure session login and returning-owner challenge; no draft disclosure until successful OTP.
7. Draft onboarding persistence/resume **scaffold** for future M2; no dynamic question bank yet.
8. Atomic owner activation → admin landing page (not website publish); roles enforce owner-only control.
9. Migration and rollback strategy compatible with existing users/tables; data-preserving operations.
10. Operator README, .env.example without secrets, smoke-test scripts and evidence report.

## 3. Out-of-scope / prohibited in M1

- No industry-dependent questionnaire, products/services collection, content seeding or SEO generation.
- No implementation of the five-theme public gallery or full BrandKit; existing public theme remains as-is.
- No default publish, no production site launch, no cross-business/tenant model, no rewrite to a different framework.
- No destructive SQL migration, credential disclosure, fake OTP/test-bypass, anonymous first-visitor ownership.

## 4. Suggested components adapted to current PHP MVC

`InstallationStateService`, `SetupGate`, `OwnerProvisioningCli`, `OwnerClaimService`, `OtpChallengeService`, `MailTransport`, `SetupDraftRepository`, `AuthSessionService`, `SetupController`, `VerificationController`, `AuditService`. Keep compatible existing router/controllers. Split domain rules from HTTP handlers. Use migration numbering and transactions.

## 5. Route contract proposal (subject to existing router compatibility)

`GET /setup` public pending screen; `GET/POST /setup/identify`; `POST /setup/otp/send`; `GET/POST /setup/otp/verify`; `GET /setup/resume` authenticated; `GET /admin` owner protected; developer CLI `php bin/bizprofile owner:provision --email=...` (specific syntax chosen during implementation and documented). Every mutating HTTP route CSRF protected, request validated, rate limited; no raw email lists exposed. Ensure original `/admin/login` remains compatible or redirects safely.

## 6. Data model acceptance constraints

Exactly one active owner per instance, dedicated owner email provision record and immutable audit events. OTP records keyed to purpose/owner/instance; no plaintext code, never log email secret bodies. Draft answers segregated by secure server session/claim ID. Email normalizing and mobile E.164 parsing are server-side. Provisioned email change invalidates sessions/challenges. Versioned schema migrations and rollback backup evidence mandatory.

## 7. Suggested task order and checkpoints

T1: inspect active DB DDL and routes; migration mapping; approval of any destructive impact (default none).  
T2: installation state and safe setup landing.  
T3: secure owner provisioning CLI and claim binding.  
T4: OTP/mail challenge and rate-limits.  
T5: provisional draft, returning login and resume.  
T6: owner activation + admin handoff/role protections.  
T7: automated tests, manual flow evidence, hardening, rollback exercise.

Checkpoint after each task: code diff, migration impact, tests and unresolved risks. Stop rather than bypass a security gate.

## 8. M1 exit criteria

All `M1-AT-*` tests in Acceptance Matrix must PASS; no high/critical vulnerabilities; application works against real MySQL and configured SMTP; no existing routes lost; proof of ownership and admin authorization enforced; full website remains unpublished; backup/restore and migrations reproducible. If SMTP not available, report BLOCKED and provide no owner activation bypass.

## 9. Developer execution prompt

> Work in the existing BizProfile PHP MVC codebase. First complete M0 gate verification on the real local environment and produce a factual closure report. Do not start M1 until the owner explicitly authorizes M1. After approval, execute only the M1 features above in task order, preserving existing routes and DB. Provide commit/diff inventory, migration scripts, test evidence and a reproducible installation guide. Do not implement M2–M5 or publish automatically. Treat Master SRS v0.4 as a reviewed draft until formal sign-off.
