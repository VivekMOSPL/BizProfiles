# BizProfile — Decision, Versioning and Handover Register v0.4

## A. Approved by user as requirements (not full SRS approval)

- Single business/DB/domain/code package per deployment; reusable independent source handover.
- Five fully functioning public themes at first production release.
- Manual explicit owner publishing.
- D04 design was closed in v0.3 for planning: private deployer-provisioned owner email + email OTP + CLI recovery.
- M0 recovery was authorized; M1 and later development are not yet authorized.

## B. Newly specified defaults requiring final sign-off

D03, D05–D08, D10, D12, D14–D24 (exact descriptions in Master SRS v0.4). These are selected to remove coding ambiguity, but cannot be falsely described as individually user-approved. No additional blocking *questions* are essential to prepare M1 code, provided security/deployment environment checks succeed.

## C. Open factual blockers, not policy ambiguities

B01: Real Windows localhost DB/PDO/host integration has not been tested.  
B02: Missing PHP admin templates in uploaded archive.  
B03: Legacy tests contain syntax errors.  
B04: SMTP provider and delivery not yet verified.  
B05: Actual DB schema reconciliation before additive migration.  
B06: App secrets present in original upload; clean release packaging/credential risk assessment required.  
These cannot be safely declared 'resolved' by documentation alone.

## D. Approval controls

SRS v0.4 status: DRAFT. User approval text suggestion: `Approve BizProfile Master SRS v0.4 as baseline v1.0, incorporating stated defaults.`  
M0 acceptance text suggestion: `Accept M0 local closure report after reviewing evidence.`  
M1 authorization is separate: `Authorize BizProfile M1 implementation per Phase One Work Order v0.1.`  
If the user chooses to authorize M1 before approving v1.0, mark it **limited authorization under a draft**, not blanket architecture approval.

## E. Release/handover artifacts (future, not yet delivered)

Independent sanitized source ZIP and Git tag; SQL schema and migrations; `.env.example`; installation and recovery runbook; owner/admin guide; all five licensed themes/asset attributions; BrandKit exports; test reports; versioned change log; backup and restore guide; handover checklist; no customer cross-contamination or centrally hosted runtime dependencies.

## F. Change ledger

| Version | Date | Change | Status |
|---|---|---|---|
| v0.1 | 2026-11-22 | Initial business architecture | Draft |
| v0.2 | 2026-11-22 | User selects five themes; manual publish; alternative ownership | Draft |
| v0.3 | 2026-11-22 | D04 first-owner mechanism closed for planning | Draft |
| v0.4 | 2026-11-22 | All remaining proposed design defaults explicitly resolved; phase work order, testing and governance consolidated | Draft, awaiting approval |

## G. Traceability

- `01_*`: Master SRS and decision definitions.
- `02_*`: Phase-one scope and stop conditions.
- `03_*`: Test and acceptance evidence required.
- This document: changes, status and handover.
- Earlier v0.3 draft and M0 report remain unchanged source references; nothing here asserts a host-side code change or release.
