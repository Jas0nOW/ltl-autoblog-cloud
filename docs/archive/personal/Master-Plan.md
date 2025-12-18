# Master Plan — LTL AutoBlog Cloud

## Project Snapshot
- Repo: `ltl-autoblog-cloud`
- Branch under review: `Phase1-Core`
- Date: 2025-12-18
- Primary runtime: WordPress plugin `wp-portal-plugin/ltl-saas-portal`
- Primary external integrations: Make.com, Gumroad

## Current State Summary
- Latest audit status (engineering): `docs/audits/2025-12-18-audit-v3.md`
  - Phase 1 feature set tracked as ✅ done in that report; smoke tests are explicitly pending.
  - Crypto hardening (HMAC v1) and masking in `/active-users` appear implemented.
- Earlier audit (`docs/audits/2025-12-18-audit-initial.md`) lists general security gaps; several are now addressed (see DONE LOG).
- **STATE C** (planning): No existing `Task:` blocks in planning docs, but **Planner-identified P1 security risks** exist in the current code and must be converted into tasks (Phase 2 security hardening sprint).

## Phase 0 Tasks

Task: Phase 0 status confirmation (no fatal broken flows)
- DoD:
  - Confirm REST routes register and respond (health endpoint returns 200).
  - Confirm Make token auth still blocks unauthenticated access.
- Tests to run:
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
  - `curl -i https://<site>/wp-json/ltl-saas/v1/health --insecure`
  - `curl -i https://<site>/wp-json/ltl-saas/v1/make/tenants --insecure` (expect 403)
- Evidence:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L7-L77` (route registration)
  - file: `docs/testing/logs/testing-log.md#L1-L40` (completed entry)
- Risk note: Prevents shipping with a fundamentally broken REST surface.

## Phase 1 Tasks

Task: Close out Phase 1 smoke tests and log results
- DoD:
  - Execute all checks in `docs/testing/smoke/sprint-04.md`.
  - Record outcomes in `docs/testing/logs/testing-log.md` (no blanks in the Sprint 04 and Phase 1 Security Tests sections).
  - Any failures become new `Task:` blocks in this Master Plan (Phase 0/1 depending on severity).
- Tests to run:
  - Follow commands in `docs/testing/smoke/sprint-04.md` (curl suite)
- Evidence:
  - file: `docs/testing/logs/testing-log.md#L1-L80`
- Risk note: Without logged smoke tests, correctness regressions can reach release.

Task: Document and confirm `/active-users` does not expose decrypted secrets
- DoD:
  - `/active-users` response contains masked `wp_app_password` (never plaintext).
  - Document where this is enforced.
- Tests to run:
  - `curl -H "X-LTL-API-Key: <api_key>" https://<site>/wp-json/ltl-saas/v1/active-users --insecure`
- Evidence:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L387-L427`
- Risk note: Accidental secret exposure through a “debug” endpoint is a common breach path.

## Phase 2 Tasks (Security Hardening Sprint if needed)

Security sprint: `docs/archive/personal/sprint-08-security.md`

Task: [P1] Remove plaintext password from Gumroad welcome email (planner)
- DoD:
  - Welcome email no longer includes any password.
  - Email contains only a login URL and a password-reset URL (or equivalent activation flow).
  - No other email template/log path includes the generated password.
- Tests to run:
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
  - Manual: trigger a Gumroad webhook “new user” creation and inspect received email body.
- Evidence:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L262-L283`
  - file: `docs/testing/logs/testing-log.md#L1-L120` (note: “Gumroad email verified, no plaintext password”)
- Risk note: Plaintext credentials in email are durable and frequently leaked.

Task: [P1] Disallow Gumroad webhook secret in query string; require header-only (planner)
- DoD:
  - Webhook rejects requests where auth is provided via `?secret=...`.
  - Webhook accepts `X-Gumroad-Secret` header only.
  - Admin UI help text no longer suggests query-string secret usage.
- Tests to run:
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
  - `curl -i -X POST "https://<site>/wp-json/ltl-saas/v1/gumroad/ping?secret=BAD" --insecure` (expect 403)
  - `curl -i -X POST -H "X-Gumroad-Secret: <secret>" https://<site>/wp-json/ltl-saas/v1/gumroad/webhook --insecure` (expect 200/ok when payload valid)
- Evidence:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L136-L156`
  - file: `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php#L214-L226`
- Risk note: URL secrets leak via logs, referrers, and browser history.

Task: [P1] Reduce blast radius of decrypted `wp_app_password` in `/make/tenants` (planner)
- DoD (choose ONE minimal control and document choice):
  - Option A: Restrict endpoint by allowlisted source IPs (config-driven).
  - Option B: Default response excludes secrets; requires explicit opt-in parameter for secrets.
  - Option C: Replace plaintext secret return with short-lived token exchange.
  - In all cases: preserve Make.com automation feasibility and document expected setup.
- Tests to run:
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
  - Manual curl with/without control enabled to show blocked vs allowed behavior.
- Evidence:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L312-L378`
  - file: `docs/testing/logs/testing-log.md#L1-L160` (note: control verified)
- Risk note: A single token leak can expose all tenant credentials at scale.

Task: [P1] Add environment/constant override for stored secrets (planner)
- DoD:
  - The system can source Make token / API key / Gumroad secret from environment or wp-config constants (override precedence), falling back to `wp_options` for backward compatibility.
  - Document which variables/constants are supported and their precedence.
- Tests to run:
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php`
  - Manual: set override values in `wp-config.php` and verify endpoints authenticate even if options are empty.
- Evidence:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php#L1-L80`
- Risk note: `wp_options` is a high-value target and commonly exposed by unrelated plugin flaws.

Task: [P2] Rate-limit IP trust model review for proxied deployments (planner)
- DoD:
  - Document whether the deployment trusts `X-Forwarded-For`.
  - If not trusted, ensure rate limiting uses `REMOTE_ADDR` only; if trusted, document proxy requirements.
- Tests to run:
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- Evidence:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L82-L122`
- Risk note: Spoofable IP inputs can weaken rate limiting.

## Phase 3 Release Candidate Gate

Status: **FAIL**
- Reason:
  - Phase 2 P1 tasks are open.
  - Smoke tests are not recorded as completed in `docs/testing/logs/testing-log.md`.

RC Gate checklist (must be evidenced to flip PASS):
- Build/package:
  - Run `powershell -File scripts/build-zip.ps1` and record output details.
- Smoke tests:
  - Complete `docs/testing/smoke/sprint-04.md` and record in `docs/testing/logs/testing-log.md`.
  - If Sprint 07 is relevant to release scope, complete `docs/testing/smoke/sprint-07.md` and record results.
- Billing security sanity:
  - Confirm Gumroad webhook auth is header-only.
  - Confirm no plaintext password is emailed.

Evidence required to declare PASS:
- file: `docs/testing/logs/testing-log.md#L1-L120`
- file: `docs/releases/release-checklist.md#L1-L200` (if updated/used)

## Evidence Rules
- Evidence must be one of:
  - `file: path#Lx-Ly` (source of truth for behavior or docs)
  - `hook: name` (WordPress hook registrations, e.g. `rest_api_init`)
- For manual tests (curl/admin UI), evidence must be:
  - A completed entry in `docs/testing/logs/testing-log.md` with date + environment + pass/fail.

## DONE LOG
- 2025-12-18: HMAC v1 tamper detection present in crypto layer.
  - Evidence: file: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-crypto.php#L1-L70`
- 2025-12-18: `/active-users` masks `wp_app_password` in response.
  - Evidence: file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L387-L427`
- 2025-12-19: **Docs Cleanup** — Professional repository structure established.
  - Archived obsolete release notes (`UPDATE-LANGUAGE-SWITCHER.md`, `CHANGELOG-i18n.md`) to `docs/archive/releases/`
  - Merged redundant workflow docs (`issue-workflow-cheatsheet.md` → `issues-playbook.md`)
  - Added "Top 10 Essential Docs" section in root `README.md`
  - All active docs categorized by purpose (Reference, Product, Billing, Testing, etc.)
  - Evidence: file: `README.md#L22-L36`, file: `docs/README.md#L1-L52`

## OPEN RISKS
- P1 (planner): Plaintext password appears in Gumroad welcome email.
  - Covered by: Task “Remove plaintext password from Gumroad welcome email”
  - Evidence seed: file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L262-L283`
- P1 (planner): Gumroad secret accepted via query param; admin UI suggests query secret URL.
  - Covered by: Task “Disallow Gumroad webhook secret in query string”
  - Evidence seed: file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L136-L156`; file: `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php#L214-L226`
- P1 (planner): `/make/tenants` returns decrypted `wp_app_password` for all active tenants.
  - Covered by: Task “Reduce blast radius of decrypted wp_app_password in /make/tenants”
  - Evidence seed: file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L312-L378`
- P1 (planner): Secrets are stored in `wp_options` with no env/constant override.
  - Covered by: Task “Add environment/constant override for stored secrets”
  - Evidence seed: file: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php#L1-L80`
