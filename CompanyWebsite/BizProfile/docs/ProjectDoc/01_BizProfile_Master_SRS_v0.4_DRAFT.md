# BizProfile — Architecture & SRS v0.4 (Consolidated Development-Ready Draft)

**Status:** READY FOR OWNER REVIEW — NOT APPROVED. **Date:** 22 November 2026.  
**Source baseline:** SRS v0.3 Draft, CompanyWebsite.zip source audit, M0 Completion Report v0.1.  
**Authority:** User requests all remaining clarifications resolved and documents to begin first development phase. This authorizes preparation of the plan, **not** automatic acceptance of M0 or implementation of M1.  
**Priority of requirements:** User-confirmed decisions > this version's explicitly proposed design defaults > v0.3 proposed elements. Existing code/DB compatibility must be verified before migration.

## A. Fixed product boundaries

1. One deployment serves exactly one business, on one independent domain/subdomain and dedicated database. Deployment code is transferable and independently customizable; no tenant switching or cross-client platform dependency.
2. New site without completed setup offers a polished, non-indexable setup-pending landing page. Public visitors never automatically become owners; only provisioned authorized owner can claim.
3. Setup captures business identity, industry/nature, organization, offering, contacts, website preferences, selected theme, BrandKit and page/content draft. Questions adapt by business category and configuration remains database-managed.
4. The owner may select any of **five complete** themes before initial production release: Blue Corporate, Dark Professional, Green Business, Purple Modern, Orange Startup. Selection drives typography, layout system, components, imagery rules, color and BrandKit composition, not simply accent color.
5. Owner-authenticated admin manages seeded profile/BrandKit/pages; draft content is not publicly published. Owner must explicitly click Publish after review.
6. Preserve current custom PHP MVC/PDO/MySQL stack and existing working functionality. Existing archive is not a completed production build; M0 findings apply.

## B. Resolved design defaults and decision dispositions

These choices remove planning ambiguity for coding estimates; their status remains **PROPOSED FOR APPROVAL**, except user-confirmed choices and the D04 planning closure. Any discovery proving a default incompatible must be raised as change control, not silently rewritten.

| ID | Decision / exact implementation default | Disposition |
|---|---|---|
| D01 | Single-business, independently transferable deployment; singleton site config. | USER-CONFIRMED |
| D02/D13 | Five complete themes mandatory at first production release, with each theme independently functional and previewable. | USER-CONFIRMED |
| D03 | BrandKit = base theme revision + industry design preset + explicit owner overrides; persist resolved version snapshot and provenance. | PROPOSED DEFAULT |
| D04 | Deployer provisions primary owner's email privately, then email OTP; no public-first-visitor ownership; protected CLI recovery. | RESOLVED FOR PLANNING in v0.3 |
| D05 | New claimant may work in a constrained draft after authorization match; final owner activation requires OTP. Returning claimant must OTP before viewing existing saved private answers. | PROPOSED DEFAULT |
| D06 | Preview-and-apply theme switch with token/content diff, separate draft revision and rollback; never overwrite user-edited page copy. | PROPOSED DEFAULT |
| D07 | Initial industry questionnaire fixtures: manufacturing, professional services, NGO; other industries supported by generic fallback schemas. | PROPOSED DEFAULT |
| D08 | Deterministic local content/BrandKit defaults; AI providers are optional adapters, never deployment or setup blockers. | PROPOSED DEFAULT |
| D09 | Explicit owner-only manual Publish; setup completion and verified email never trigger publish. | USER-CONFIRMED |
| D10 | Preserve existing PHP custom MVC + PDO/MySQL; migrate additively only after DDL inspection and backup. | SOURCE-ALIGNED DEFAULT |
| D11 | No tenants table, no central management, no switching among separate business sites. | USER-CONFIRMED |
| D12 | Documents stay DRAFT until explicit approval; Git tags and baseline only after approval. | USER-DIRECTED |
| D14 | SMTP verified before production owner activation; outage preserves drafts with a blocked/retry state, never bypasses OTP. | PROPOSED DEFAULT |
| D15 | English initial interface; translatable strings and future Hindi-ready content columns/schema where feasible. | PROPOSED DEFAULT |
| D16 | Logo upload supported; fallback text/monogram placeholder until owner provides logo; AI images only if connected/allowed. | PROPOSED DEFAULT |
| D17 | No automatic publication, automatic SEO indexing, fake client lists, fabricated testimonials or unverified certificate claims. | SOURCE/SAFETY DEFAULT |
| D18 | Application states installation, onboarding and publishing independently; transitions audited and tested. | PROPOSED DEFAULT |
| D19 | OTP 6 digits, cryptographically generated, TTL 10 min, max 5 attempts, 60-second resend cooldown, maximum 5 sends/hour/account; all configurable with IP-wide limits and transaction atomicity. | PROPOSED DEFAULT; SECURITY REVIEW BEFORE PRODUCTION |
| D20 | Server-side sessions with Secure/HttpOnly/SameSite cookies (HTTPS); session rotation on verification/login; CSRF tokens and role checks on every mutation. | PROPOSED DEFAULT |
| D21 | Owner email claim is case-normalized and deployment-bound; new email claim rejected unless matching provision; phone alone never grants access. | D04 DETAIL |
| D22 | Developer/local CLI rotates provisioned email only with host access, explicit operator identity, audit record, OTP/session invalidation; owner email change requires re-verification. | D04 DETAIL |
| D23 | App failure fallback: safe 503 setup-unavailable page for missing DB/schema; never silently self-install from untrusted public route. | PROPOSED DEFAULT |
| D24 | Code handover must include sanitized source, migrations, docs, build/test results, asset licenses and backup/restore; no .env, credentials, session keys or other clients' data. | USER-ALIGNED DEFAULT |

## C. State machine and route boundary

Installation: `schema_missing`, `schema_ready`, `owner_provisioned`, `owner_activated`, `operational`, `error` (distinct, backend-only). Onboarding: `not_started`, `in_progress`, `awaiting_email_verification`, `ready_for_admin`, `complete`. Publication: `unpublished`, `preview`, `published`, `maintenance`.

Priorities: handle system health and static assets; run route guards; if database unavailable respond HTTP 503; if schema missing show non-sensitive setup-unavailable and require CLI installer; if no activated owner show setup landing and limited claim routes; if published serve public site; if unpublished serve neutral pending page except authenticated owner preview/admin. `GET /admin/login` must never disclose unverified draft data. No change to existing public route contracts until audited.

## D. User journeys and security sequencing

**First owner:** deployer provisions email through protected local mechanism → visitor opens unconfigured domain → choose Setup → enter name, business, email and mobile → neutral claim check → authorized matching email allows isolated provisional wizard draft → save through conditional sections → five-theme gallery → BrandKit and page-preview proposal → final summary → OTP verified (or current trusted challenge binding) → atomic activation of exactly one owner → admin entry → owner explicitly publishes. Until verified, admin privileges do not exist.

**Returning setup owner:** neutral email prompt → one-time OTP → authenticated existing draft loaded → resume persisted step. Existing private answers are inaccessible before OTP. Unknown email always gets generic messaging.

**Published site:** public homepage served; owner uses separate secure admin login; edits create draft revisions and do not mutate published snapshot until owner publish.

**Failure:** timeout/retries preserve authorized draft, OTP outage blocks activation, repeated claim requests cannot create duplicate owner, concurrent publish uses transactional version guard; break-glass owner recovery is CLI-only and auditable.

## E. Database and migration policy

Use existing tables `website_settings`, `pages`, products/services/media/users where compatible. Create only additive, versioned migration changes required for M1. Candidate tables: `app_installation_state` singleton, `owner_claims`, `verification_challenges`, `onboarding_sessions`, `setup_audit` (or shared `audit_logs`). Future M2–M5 entities for questionnaire, themes, BrandKit, content provenance, seed_runs and releases are not authorized in M1. Never assume prior suggested table structures are already present. Use idempotent migration up/down where safe; back up data first; maintain unique active-owner invariant at the database/service layer.

## F. Theme acceptance policy — FUTURE M3, NOT PHASE ONE

Five selectable public themes: `blue_corporate`, `dark_professional`, `green_business`, `purple_modern`, `orange_startup`. Every theme must have real PHP layout/assets; validated manifest; responsive home/about/offerings/contact/menu/footer; accessibility keyboard/contrast checks; BrandKit composition; desktop/tablet/mobile visual snapshots; dynamic content; secure preview; owner-admin editing; theme switch without copy loss; rollback. The existing archive only evidences the blue corporate layout, so five-theme capability is a **release requirement, not an existing feature**.

## G. First-phase permission and gates

**Gate G0 — M0 acceptance:** Patch v0.1 applied to backed-up local working copy; PHP routing and DB integration actually work; missing admin view restored or expressly documented/recovered; host smoke tests recorded; user accepts M0 report update. Current G0 = OPEN/BLOCKED. 

**Gate G1 — M1 implementation authorization:** Owner explicitly approves this draft or the separately scoped M1 spec and directs development. Current G1 = NOT GIVEN. 

**M1 functional scope after authorization:** installation state/guard, deployer-provisioned owner email, secure owner identification and OTP, limited draft claim state, secure return login, resume scaffold, setup landing, admin access handoff, audit and tests. Exclude dynamic industry questionnaire (M2), five-theme build (M3), content seeding (M4), and public publish workflow (M5) except necessary state placeholders.

## H. Source references and verification honesty

Derived from v0.3 architecture and M0 recovery report. The M0 patch's 41-file PHP lint and HTTP status smoke tests are reported, but full MySQL integration/admin login on user's Windows instance remains untested. Nothing in this document asserts that database connectivity, email delivery, five themes, or an end-to-end website already works. No project repository, host, or production database has been modified in this revision.

## I. Change control and approval block

`v0.1` initial draft → `v0.2` five-theme/manual-publish choices → `v0.3` D04 planning closure → `v0.4` consolidated defaults, stage gates, implementation and test briefs. Proposed next approved tag only after confirmation: `BizProfile-SRS-v1.0`. Until then status remains DRAFT, owner approval unsigned, M1 not authorized. Keep change requests and test evidence in `docs/` when approved for repository write.
