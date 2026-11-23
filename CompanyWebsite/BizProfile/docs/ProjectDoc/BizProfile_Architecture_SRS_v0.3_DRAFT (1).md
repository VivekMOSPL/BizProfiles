# BizProfile — Single-Business Self-Configuring Website
## Architecture, Product Requirements & Approval Register

**Document version:** v0.3 | **Status:** REVIEWED DRAFT — NOT APPROVED | **Date:** 22 November 2026 | **Supersedes:** v0.2-DRAFT  
**Deployment model:** One independently deployed website, one business, one database, one domain/subdomain.  
**Example:** `prismsadhana.datachron.in` hosts the BizProfile published code, schema and its own business data.  
**Approval rule:** No approved baseline or implementation authorization until explicit owner confirmation. After approval, freeze a tagged v1.0 baseline with change log.

## 1. Executive summary and non-negotiable boundaries
BizProfile is a reusable PHP/MySQL website **product**, not a SaaS managing customers from a central console. Each deployed copy is independently transferable, maintainable, customizable and handover-ready. A new deployment shows an attractive restricted setup experience until an authorized business owner completes configuration. The chosen design theme is a **full website design system**, not merely a color selection; the theme influences BrandKit generation and page composition. All generated website content remains database-managed through the local admin panel.

**In scope:** recovery of blank pages; installation/configuration state; authorized owner claiming; email OTP; resumable wizard; conditional business questions; theme gallery and previews; BrandKit creation; content seeding; page editing; SEO; media; approval/publishing; audit; deployment/handover.  
**Out of scope:** shared tenant directory, multi-business admin, centrally hosted business data, mandatory AI provider, automatic fabrication of legal credentials, forcing payment/ecommerce features on every business.

## 2. Source audit — observed versus proposed
Source examined: uploaded `CompanyWebsite.zip`, `CompanyWebsite/BizProfile/`. The repository includes custom PHP MVC routing (`index.php`, `includes/Core/Router.php`), PHP 8.1+ Composer requirement, PDO/MySQL infrastructure, controllers for home/pages/products/services/admin/auth/theme, SQL schemas, and theme classes. The current root route directly maps to `HomeController@index`, not a setup-state router. `includes/bootstrap.php` initializes `APP_ENV` / `APP_DEBUG` before reading `.env`; actual blank-page runtime cause is **not verified**. `ThemeSelector.php` declares five themes: Blue Corporate, Dark Professional, Green Business, Purple Modern, Orange Startup; `Theme.php` also names Default and Admin. The uploaded public layout directory contains `themes/blue_corporate/layout.php`, with shared renderers; other named public layouts are not evidenced in the archive. `ThemeSelector::getThemes()` accesses `is_active` although the shown catalog entries do not define that key; this needs verification/remediation. No live browser, PHP+database integration, email delivery or theme render tests have been performed. The source contains an `.env`; never disclose its secrets and exclude it from handover archives.

**Evidence grading:** CONFIRMED IN SOURCE for these file observations; PROPOSED for all new behavior; NOT TESTED for runtime, setup flows and emails.

## 3. Deployment, installation and ownership model
Each deployment has one `business_profile` identity, one active public website and optional multiple local admin/editor users. Use a unique instance ID and deployment-specific key. The database may be installed with schema and industry/theme reference records but no business-specific data. Distinguish install state (`schema_ready`, `setup_locked`, `owner_claimed`), onboarding state (`not_started`, `in_progress`, `awaiting_verification`, `ready_for_review`, `complete`) and publication state (`unpublished`, `preview`, `published`, `maintenance`). Do not infer readiness merely from row count.

**Unconfigured public experience:** show polished setup-pending page and link to authenticated setup entry without exposing owner identity or internal details. Only authorized setup owners can progress to sensitive pages. Search engines should not index incomplete sites. Static assets, health, authentication and verification paths remain functional.

**Initial claim protection — D04 RESOLVED FOR PLANNING IN v0.3:** Use deployer-provisioned primary owner email, configured privately before the domain is opened for enrollment, with email OTP as proof of mailbox control. A stranger must never acquire ownership because a website is unconfigured. No invitation token needs to be given to the owner. Identification and setup can be drafted provisionally only under a constrained, non-privileged session tied to the authorized email; previous/private answers and owner/admin privileges require prior OTP. Final email OTP is mandatory before owner activation; earlier verification may satisfy this if still valid under server policy. If owner email is not provisioned, public users see a generic setup-unavailable/request-access page. Recovery is via authenticated server CLI/deployer access with auditable actions; never via unauthenticated first-visitor ownership. See section 24 for binding rules and failure handling. D04 is a chosen design direction, not authorization to implement M1.

## 4. User journeys
**A. First-time provisioned owner:** open domain → setup welcome → supply name, company, email, mobile → match the provisioned owner email without revealing account existence → create provisional draft owner → conditional wizard with autosave → theme gallery and live preview → BrandKit design → page generation preview → final review → send and verify email OTP → activate owner account → access admin dashboard → explicitly publish.

**B. Existing incomplete setup:** open domain → sign in using registered email → neutral response to prevent account enumeration → email OTP verification before revealing saved data → resume most recent valid step → continue without duplication.

**C. Already published:** public visitor sees website; owners sign in through separate secured admin route; onboarding changes remain editable within admin. **D. Recovery:** provisioned owner email unavailable, email changed, OTP failure, SMTP outage and interrupted DB seed have documented recovery with restricted deployer involvement and audit.

## 5. Identity, OTP and authorization policy
Owner identity fields: legal/display owner name, public business name, normalized email, normalized E.164 mobile where possible. Email is primary login identifier; mobile is contact and potential secondary factor only after separately verified. Normalize and compare safely; existing email/mobile matching must never leak account existence. OTP: cryptographically random, short expiry (proposed 10 minutes), one-time consumption, hashed storage, max attempts, resend cooldown, rate limits by IP/account, secure logs without OTP bodies. Default secure session cookie, CSRF and fixation protection; an approved secure first-owner authorization method plus verified email required for initial ownership claim. Roles: owner, admin, editor, viewer; access enforced server-side. When business info or admin email changes, require re-verification as appropriate. SMTP provider config and real delivery test are deployment prerequisites.

## 6. Proposed onboarding steps and branching
0. Welcome + provisioned-owner/account lookup (D04 design resolved). 1. Owner/contact details. 2. Business identity (trading/display/legal name, incorporation type, year, operational address). 3. Business classification (industry, subindustry, B2B/B2C, audience, geography, product/service model). 4. Contextual questions (manufacturer: capabilities, catalog, capacities; services: packages and process; nonprofit: mission and programs; software: feature groups and demo CTA; retail: categories). 5. Web identity (domain, email, socials, locations, contact options, privacy). 6. Theme selection with gallery, 3 device previews, accessible variant and seeded example. 7. BrandKit settings: logo upload/placeholder, theme tokens, fonts, voice and imagery. 8. Pages & modules selection and navigation. 9. Content seed proposal, factual review and SEO. 10. Final summary, email OTP if not verified, authorized completion, admin handoff.

Save draft after each meaningful field/step with optimistic concurrency and a visible saving state. Previous and Next must never lose answers. Conditional fields must be valid on server; branch changes archive irrelevant answers instead of silently erasing them. Wizard schema version is recorded and migration policy defined.

## 7. Theme gallery and design selection — mandatory core feature
**Theme = layout templates + design tokens + section patterns + font pairing + component styling + imagery rules + typography scale + header/footer variations + mobile behavior + accessibility constraints.** BrandKit is built from the selected theme plus business identity, industry defaults and owner overrides. A theme is not equivalent to a BrandKit. Let the owner choose by visual thumbnails, category, industry suitability and live device preview. Offer a recommended theme but allow manual selection.

**MANDATORY INITIAL RELEASE theme catalog (five, user-selected):** Blue Corporate (industrial/B2B), Dark Professional (technology/consulting), Green Business (sustainability/nonprofit), Purple Modern (creative/software), Orange Startup (retail/startups). These names already appear in source, but only Blue Corporate layout is evidenced. All five must be complete, independently previewable and pass functional, responsive and accessibility verification **before the initial release**. An incomplete theme may appear only in internal development tools, not a production selection gallery. Add new themes by manifest and reusable blocks, not parallel hardcoded websites. Admin theme is separate from public theme and is never offered as a customer public style.

**Mandatory per-theme fields:** stable slug/version, display name, industry tags, screenshot/thumb, layout manifest, supported sections/pages, token defaults, logo placement variants, contrast pairs, RTL/i18n capability if applicable, compatibility level, active/tested flag. Theme switching should create a preview revision with proposed token and layout changes; retain business data/edited copy; require owner approval before publication. Provide compare and rollback. Switching design must not reseed or delete business content.

**BrandKit generation inputs:** business name, entity type, industry, preferred tone, selected theme, preferred colors, logo, target audience, locale. **Outputs:** color palette and accessible combinations, typography and font fallbacks, wordmark/logo asset rules (do not manufacture actual logo without generator), button/card/banner tokens, imagery style, iconography, tone of voice, headline/tagline proposals, social graphic prompt templates, design-system CSS variables. Source tags and approval/version status for each asset. If AI/image service unavailable, deterministic templates and placeholder/owner upload are the baseline.

## 8. Content and page generation
Generate database-backed page tree with page sections; default Home, About, Products/Services as applicable, Contact, Privacy and Terms when required; configurable conditional sections (manufacturing process, certifications, gallery, NGO programs, team, FAQ, demo, lead form). Build a page content schema with headings, paragraphs, CTAs, visual references, metadata, navigation and footer components. Seed draft text only; label generated examples and unknown facts. No invented accreditation, customer logo, quantified metrics, testimonials, registration or compliance. Uploaded media must use safe paths, allowlist, scanning or equivalent safeguards, size limits and accessible alt text. Include SEO URL slug, meta title, description, canonical and robots settings; prevent premature indexing. Admin edits update draft revisions, not live pages until publish.

**Idempotence rule:** key seeds by `template_slug + template_version + business_id + section_key`; record `seed_run_id`, provenance, checksum, owner edit lock and status. Running again must not duplicate pages, or overwrite manually edited or approved text. Use preview diffs and selective re-generation.

## 9. Admin management and publishing
Dashboard: setup checklist, verification, site preview, publish readiness. Management: business/contact/locations, user roles, industries and answers, public theme and BrandKit, logo/media, home/about/offerings/news/gallery, dynamic sections, menus/footer, SEO, forms/leads, privacy, email delivery, audit and backup/export. State transitions: draft → preview → approved → published; owner may revise after publish while public version remains stable. Roles and approval options configurable, with owner final publish rights. Restore previous published snapshots with explicit rollback; audit actor/time/diff. Separate platform developer-only settings from owner-facing business content.

## 10. Recommended schema and compatibility approach
Extend existing schema through numbered SQL migrations with backup and rollback procedures. Proposed data model: `app_installation_state` (singleton), `business_profile` (singleton), `users` (existing adaptable), `owner_claims`, `verification_challenges`, `onboarding_sessions`, `onboarding_answers`, `industry_definitions`, `questionnaire_versions`, `question_definitions`, `theme_catalog`, `theme_revisions`, `brandkit_versions`, `brandkit_assets`, `page_versions`, `page_section_versions`, `content_provenance`, `seed_runs`, `publish_releases`, `audit_logs`. Reuse existing `website_settings`, `pages`, product/services/media tables when safe; inspect full DDL before deciding additive migrations. No tenant ID column needed for isolation: database per business is the isolation boundary. Apply foreign keys, indices, uniqueness, soft deletion/versioning and transaction boundaries.

## 11. Architecture and routing
Retain custom PHP MVC and PDO. Add `InstallationStateService`, `SetupGateMiddleware` (or router pre-dispatch equivalent), `OwnerClaimService`, `OtpService`, `OnboardingSchemaService`, `ThemeCatalogService`, `BrandKitComposer`, `SeedOrchestrator`, `WebsitePublisher`, `AuditService`. Suggested routes: `GET /setup`, `POST /setup/identify`, `POST /setup/claim`, `GET/POST /setup/step/{key}`, `POST /setup/otp/send`, `POST /setup/otp/verify`, `GET /setup/review`, `POST /setup/complete`, `GET /admin/website-preview`, `POST /admin/publish`; CSRF and authorization for mutation endpoints. Existing public, admin and API routes remain compatible. Serve public site only when published, with neutral default while pending. Exception handler must preserve request ID, log server side, display safe human-readable error page; fix `.env` load order.

## 12. Security, privacy and operations
Mandatory: secure bootstrap, no secrets in ZIP/repository, session cookie Secure/HttpOnly/SameSite, credential hashing, CSRF, escaping/CSP improvements, prepared SQL, request validation, rate-limited OTP, safe file uploads, email verification, approved first-owner authorization control, separate editor privileges, audit trail, database backups, least-privilege DB users, SMTP setup/monitoring, HTTPS and correct reverse proxy configuration. Verify CORS for APIs and never use open wildcard on credentialed APIs. Privacy fields and consent must be business-appropriate; avoid claiming verified legal status without supporting evidence. Pre-handover rotate deployer credentials/secrets and verify recipient access.

## 13. Packaging, customization and handover
Deliver standalone deployable release ZIP or Git tag excluding `.env`, caches, logs, `.vs`, secrets, local uploads unless expressly included, and development-only files. Provide code, schema and migrations, sample `.env.example`, installation instructions, theming spec, seed templates, admin manual, backup/restore, upgrade procedure and CHANGELOG. Support developer code-level customization of a single client deployment without modifying another client's site. Recommended maintenance: immutable upstream base release plus deployment-specific extension directory/config or Git branch, with documented merge path. No central coupling required at runtime. Branding, content and images belong to the client deployment; preserve export/import pathways for handover. Identify software licensing and third-party asset licenses before transfer.

## 14. Nonfunctional requirements and acceptance
Performance: fast first render on typical shared/VPS hosting, optimize pages/assets/caching; set numeric budgets after baseline measurements. Accessibility: keyboard navigation, responsive/mobile-first layouts, readable contrast and error notices; target WCAG 2.2 AA where practical. Reliability: transactional seeding, restartable setup and safe failed-email recovery. SEO: canonical, sitemap and robots only when published. Observability: actionable logs and install health diagnostics without secrets. Maintainability: explicit interfaces, migrations, tests, consistent coding standards and no business-specific hardcoding in shared core.

**Acceptance scenarios:** A01 blank-page error fixed with evidence; A02 empty schema shows setup-pending safely; A03 unauthorized stranger cannot claim; A04 authorized new owner saves and resumes; A05 existing owner verifies OTP before accessing drafts; A06 OTP expires and rate-limits; A07 questions change by industry; A08 all five fully functional, previewable and independently verified themes before first release; A09 chosen theme generates coherent BrandKit; A10 repeat seeds do not duplicate/overwrite edits; A11 admin edits remain draft until publish; A12 public website renders only published version; A13 theme switch preserves content and supports rollback; A14 export/backup/handover works on independent instance; A15 mobile/accessibility checks; A16 no secrets in publish package. Test states: PASSED/FAILED/BLOCKED/NOT RUN with evidence location.

## 15. Phased delivery and evidence gates
**M0 — Recovery:** PHP/DB/route/blank-page diagnosis, config error handling, secure logging, test baseline. **Gate:** real route renders or exact blocker evidenced.

**M1 — Ownership & installer:** installation state, onboarding gate, deployer-provisioned owner email enrollment (D04 design), verified login, OTP, restore interrupted wizard. **Gate:** security and recovery tests pass.

**M2 — Metadata wizard:** industry taxonomy, conditional forms, onboarding persistence. **Gate:** manufacturer + service + NGO branch scenarios.

**M3 — Theme & BrandKit:** implement theme registry and all five complete tested themes, device previews, BrandKit composition/versioning, and non-destructive switching; each theme needs an evidence record. **Gate:** all five themes pass desktop/tablet/mobile rendering, accessibility, core-page/function coverage, theme/BrandKit consistency, and switching without content loss. No first release with fewer than five.

**M4 — Content seeding/admin:** templates, provenance, drafts, admin modules, selective reseed. **Gate:** preview and edit flow with no duplication.

**M5 — Publishing/handover:** explicit publish, SEO, backups, operational guide, handover export and acceptance pack. **Gate:** independent PrismSadhana-style deployment tested end to end.

Implementation is not authorized by this planning draft.

## 16. Decision & clarification register
| ID | Topic | Proposed default | Approval status |
|---|---|---|---|
| D01 | Architecture | One website / one database / one business | User-confirmed direction |
| D02 | Theme selection | Gallery step with 5 complete themes before initial release | User-selected in v0.2; baseline unapproved |
| D03 | BrandKit behavior | Selected theme + industry + owner overrides creates versioned BrandKit | Proposed |
| D04 | Claim protection | Deployer-provisioned primary owner email + email OTP; protected CLI recovery; no public self-claim | RESOLVED for v0.3 planning; SRS baseline not approved; M1 not authorized |
| D05 | Email verification timing | Existing owner before resume; new owner by final activation | Proposed |
| D06 | Theme changes after launch | Preview, compare, approve; preserve copy; rollback | Proposed |
| D07 | First templates | Manufacturing first, services second, NGO third | Proposed |
| D08 | AI services | Optional integrations, deterministic offline defaults | Proposed |
| D09 | Publishing | Separate owner-controlled Publish after admin review; no auto-publish | User-selected in v0.2; baseline unapproved |
| D10 | Platform stack | Preserve PHP MVC / PDO / MySQL | Proposed, source-grounded |
| D11 | Scope | No multi-tenant/multi-business controller | User-confirmed direction |
| D12 | Document governance | Draft v0.1, approval freezes v1.0, then version changes | Proposed |
| D13 | Initial theme deliverable | All 5 themes ready, functional, responsive and validated before first release | User-selected in v0.2; baseline unapproved |
| D14 | Owner email setup | SMTP config required; fallback for outage | Proposed |
| D15 | Localizations | English first; Hindi-ready data model | Proposed |
| D16 | Branding images | Owner upload; optional image generation integration | Proposed |

## 17. Review checklist and version policy
**Review priorities:** 1. Exactly one business per deployment; 2. first-visit claim authorization; 3. five theme completeness and selection UX; 4. selected theme's impact on full BrandKit; 5. draft/published split; 6. secure handover; 7. migration compatibility and source defects; 8. test evidence.

**Version lifecycle:** `v0.1-DRAFT` (prior); `v0.2-DRAFT` (prior); `v0.3-DRAFT` (this decision closure); `v1.0-APPROVED` only after explicit approval; `v1.1` additive changes; `v2.0` breaking architectural changes. Every revision records date, author, changed sections, decisions, approval and scope impact. Store approved master in the project repository under `docs/architecture/BizProfile_MasterPlan_v1.0.md` plus matching `CHANGELOG.md` and traceable decision register. **This draft is not that approved repository baseline.**

## 18. Current unresolved risks
- The precise reason for blank `localhost:8000` page is not runtime-confirmed.
- Five themes are listed by source, but only one public theme layout is present in the reviewed ZIP.
- Current DB migrations and admin data compatibility need an execution-grade schema comparison.
- SMTP/email-OTP infrastructure is untested; D04 design is resolved for planning, but its M1 implementation and security tests remain unauthorized.
- Theme preview snapshots/assets need production-safe creation, licensing and testing.
- Some listed PHP theme metadata lacks `is_active` and may trigger warnings; assess and repair.
- Existing secrets in project archives are a release hygiene risk.

**Approval request:** Review the v0.2 decisions and open items. User selections are recorded as directions for this draft, **not** full document approval. D04 is resolved in v0.3 for planning; remaining design and verification requirements must be reviewed before any v1.0 baseline approval. Do not implement, deploy, seed data, or commit a baseline based on this draft.


## 19. v0.2 review outcome, revision ledger and implementation hold
| Revision | Date | Input | Change | Result |
|---|---|---|---|---|
| v0.1 → v0.2 | 22 Nov 2026 | Theme delivery = five | M3, A08, theme catalog and D02/D13 now mandate all five for initial release | Recorded user decision; NOT an approved release |
| v0.1 → v0.2 | 22 Nov 2026 | Initial owner security = alternative | Removed assumed mandatory invitation token; proposed deployer-provisioned owner identity and email OTP, with security gate and recovery | OPEN: selected alternative design not yet confirmed |
| v0.1 → v0.2 | 22 Nov 2026 | Publishing = manual | D09 states explicit owner Publish, separate from setup completion | Recorded user decision; baseline remains DRAFT |
| v0.1 → v0.2 | 22 Nov 2026 | Additional comments = none | No new functional scope added | Recorded |

**Review status:** Three selections captured. No blanket approval, no source changes and no production authorization. Any prior language implying staged first-release theme delivery or mandatory invitation-token security is superseded by this section and corresponding revised sections.

## 20. First-owner security alternatives — historical v0.2 review (superseded by section 24)
**Option A (selected for v0.3 design): Deployer-provisioned email.** During setup of the independent deployment, developer configures the expected owner's verified delivery email via private installer/CLI or protected configuration. Public site takes owner name/business/email/mobile, sends email OTP to the provisioned address, and allows an authenticated wizard to proceed. If a new owner enters a non-matching email, show neutral instructions for requesting setup access. No claim is awarded solely because a contact row is absent. Advantages: simple owner UX, no separate invitation token in owner's hands. Tradeoff: deployer must accurately preconfigure email; recovery needs controlled procedure.

**Option B (not selected): Request-and-approve enrollment.** An unrecognized visitor can submit a limited claim request, and the deployer approves it via a secure, authenticated local installer/CLI after independent confirmation. Email OTP follows approval. Advantages: no pre-entered owner email. Tradeoff: manual coordination and extra secure administration surface.

**Option C (not recommended as sole control): Email OTP to arbitrary first entrant.** This only proves control of that person's mailbox, not entitlement to the website. It must not activate site ownership by itself.

**Decision v0.3:** Option A selected as the documented D04 design, with protected CLI recovery and OTP sequencing described in section 24. This does not authorize M1 coding or approve the full SRS.

## 21. Five-theme definition of done (release gate)
The first production release must include five individually implemented themes: `blue_corporate`, `dark_professional`, `green_business`, `purple_modern`, `orange_startup`. For **each**, verify: (1) actual PHP layouts and assets present; (2) theme manifest and compatibility metadata valid; (3) desktop/tablet/mobile preview; (4) homepage, about, products/services, contact, navigation and footer; (5) BrandKit tokens, accessible typography/color/spacing and override behavior; (6) dynamic content and SEO integration; (7) changes editable in admin; (8) theme switch preview retains content; (9) keyboard/accessibility checks; (10) failure/rollback checks. All five theme evidence reports must be PASSED before an initial release is considered acceptable.

## 22. Manual publish policy (confirmed user direction; draft baseline)
Setup completion and owner verification can activate the admin dashboard, generate previews and prepare content, but **must not automatically make draft website content public**. Only a verified authorized owner using an explicit Publish action may promote a validated version. Store published release ID and draft revision separately; record who published, when, and what changed; support preview, rollback and republish. Unpublished visitors see an appropriate neutral coming-soon/maintenance page, never private draft content.

## 23. Pending approval checklist
- **D04 / RESOLVED FOR PLANNING:** Deployer-provisioned primary owner email + OTP + protected recovery; implementation remains M1-gated and unapproved.
- **D03 / REVIEW:** Confirm theme selection plus business type drives the full editable BrandKit; generated logos/images only when actual capabilities exist.
- **D05 / REVIEW:** Verify new-owner journey may draft first but cannot access old private data or become admin until identity and enrollment checks pass.
- **D14 / REVIEW:** Decide SMTP provider and installation-time test requirements (no secrets in docs).
- **D15 / REVIEW:** Confirm initial user-interface language and localization approach.
- **D01/D11, D02/D13, D09:** Directions recorded; no further tradeoff requested now.

**Approval gate:** Require explicit statement approving a specified document revision. Until then, this is **v0.2-DRAFT / NOT APPROVED / NOT IMPLEMENTATION AUTHORIZATION**.


## 24. D04 — Initial ownership and recovery specification (v0.3 decision closure)

**Decision:** Use a privately provisioned authorized owner email (Option A); no invitation token handed to the customer; no automatic ownership by the first public visitor. One independent business website, one primary owner, with possible additional authorized admin/editor accounts later. **Planning disposition:** RESOLVED. **Release status:** NOT IMPLEMENTED / NOT TESTED. Full SRS is still DRAFT.

### D04.1 — Deployment prerequisites and data authority
1. Deployer installs the independent schema and assigns a random instance ID; installs application-specific secrets via protected environment/configuration (not public HTML or Git).
2. Deployer sets `authorized_owner_email` using an authenticated, server-local CLI installer or equally protected deployment operation. Save a normalized owner email and auditable provisioning metadata. Prefer storing a keyed hash for lookup plus appropriately protected delivery address; encryption key must be outside the database. Do not place a plain authorized email in public site markup.
3. Until email provisioned and SMTP tested, keep `setup_locked`; website shows a neutral restricted page and does not allow a public ownership claim.
4. The deployer confirms the email with the customer through an independently established contact channel. Business/domain ownership is not proven by email OTP alone; this provisioning is the independent authorization step.
5. Once provisioned, `owner_claim_pending`; public setup displays owner identity form but never reports whether a given email or phone exists.

### D04.2 — Initial-owner experience
1. Visitor enters name, business name, email and mobile. Server normalizes email/phone, applies CSRF/bot/rate limits, responds with the same neutral language whether the email matches or not.
2. For a matching provisioned email, permit a *provisional* wizard with isolated, non-sensitive drafts only; do not expose any pre-existing owner data or privileged management screens. For unmatched email, do not create an owner account or permit persistent changes to the business website; show request-access guidance.
3. At the final step, send OTP only to the authorized provisioned email; require successful OTP verification, non-expired setup state and a valid server-side authorization match before activating owner and linking draft data.
4. After activation, move to authenticated admin dashboard, with site still **unpublished** until explicit Publish action. Login sessions use secure rotation, idle expiry, CSRF protections and server-side access control.
5. If OTP is verified earlier in the session for access to previous private saved drafts, a secure short-lived verification binding may prevent redundant OTP at final step, subject to server-side expiry/re-authentication rules.

### D04.3 — Returning owner / interrupted setup
- Prior owner already established: generic identification response; OTP must be verified **before** reading saved private answers or resuming authenticated setup; previous mobile/name/business alone are insufficient.
- Valid OTP authenticates the provisioned owner; resume last persisted authorized step. Repeated requests cannot create a second owner or overwrite an existing owner record.
- Browser/session interruption preserves server-side progress; no raw OTP, privileged cookie or secret is stored in client-side local storage.

### D04.4 — OTP and session security rules
- Cryptographically secure numeric or alphanumeric challenge; recommended 6-digit numeric with 10-minute expiry, max 5 attempts per challenge, resend cooldown >=60 seconds, daily and IP/account rate limits; enforce server-side.
- Store only a salted/peppered secure OTP verifier or keyed hash, never plaintext OTP or full OTP in logs. Bind challenge to intended account, purpose, instance and session/transaction; invalidate on success and on owner-email change.
- Generic verification messages, no user enumeration or sensitive leak; use transport TLS, HTTPS, secure/HttpOnly/SameSite cookies, CSRF tokens, session-id rotation after verification and audit of security-relevant events.
- SMTP outage must not bypass OTP or grant admin rights. Owner can retry after repair; preserve drafts securely.

### D04.5 — Recovery and change of authorized owner email
- If authorized email was mistyped, compromised or unavailable, **never** allow public re-claim by entering a different email. Deployer uses server-local authenticated CLI with OS/filesystem access, auditable reason and explicit backup; recovery requires independently verified customer authorization.
- CLI rotates pending claim credentials/challenges, replaces authorized owner email, invalidates outstanding OTPs and sessions as needed, then requests fresh OTP verification. Changes to an active owner require stronger protected break-glass procedure and audited recovery; do not silently transfer ownership based on phone matching alone.
- Deployer separation from owner: no persistent universal admin login/backdoor. Prefer one-time local recovery capability with explicit operational access controls; remove any temporary bootstrap tokens after claim.

### D04.6 — Proposed storage and acceptance cases
**Design-only schema delta:** Extend `app_installation_state` and `owner_claims` (or existing equivalents) for authorized email binding, lifecycle state, attempt and recovery metadata; `verification_challenges` for purpose, expiry, attempt count and consumption; `audit_logs` for provisioning, recovery, claims and verification. Exact SQL remains subject to existing DDL compatibility review during authorized M1.

**Acceptance tests required at M1:**
- D04-T01: no provisioned owner → no public ownership claim.
- D04-T02: wrong email/mobile → generic response, no access, no account enumeration.
- D04-T03: matching provisioned email → provisional wizard; no sensitive record access without OTP.
- D04-T04: valid OTP + valid provisioning → exactly one owner activated; admin access; site unpublished.
- D04-T05: invalid/expired/replayed OTP → denied; attempt/resend limits enforced.
- D04-T06: interrupted setup → authorized owner verifies and resumes without data loss.
- D04-T07: parallel claim attempts → only one owner; database uniqueness/transaction protections.
- D04-T08: SMTP unavailable → no bypass; safe retry after service restored.
- D04-T09: email lost/changed → CLI-only audited recovery, session/OTP invalidation.
- D04-T10: owner login and publish permissions remain distinct; no publish from OTP alone.

**Decision ledger:** 2026-11-22 | v0.2→v0.3 | D04 option A selected as project planning default in response to user instruction “complete D04” | Owner onboarding/claim section, M1 prerequisites, acceptance tests updated | Baseline still unapproved, M1 not authorized.
