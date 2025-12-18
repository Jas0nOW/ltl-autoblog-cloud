# Master Plan — LTL AutoBlog Cloud (V1 Launch)

## 1) Current State Snapshot (max 20 bullets, jeder Bullet mit Evidence-Pfad)

- WordPress-Plugin Entry + Hooks: Plugin lädt `LTL_SAAS_Portal::instance()`, registriert Activation/Deactivation Hooks (DB Setup) — Evidence: `wp-portal-plugin/ltl-saas-portal/ltl-saas-portal.php`
- Haupt-Komponente initialisiert Admin + REST + Shortcodes — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`LTL_SAAS_Portal::init()`)
- Customer UI läuft über Shortcode `[ltl_saas_dashboard]` inkl. Login-Gate — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`shortcode_dashboard()`)
- Setup-Progress Block: Step 1 (WP verbinden), Step 2 (RSS + Settings), Step 3 (Plan Status), Step 4 (Last Run) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- Persistenz: Plugin erstellt drei DB Tabellen: `wp_ltl_saas_connections`, `wp_ltl_saas_settings`, `wp_ltl_saas_runs` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- WP-Connection wird pro User gespeichert: `wp_url`, `wp_user`, `wp_app_password_enc` (verschlüsselt) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- Verschlüsselung at-rest: AES-256-CBC + HMAC (v1 Format), Keys aus WordPress Salts — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-crypto.php`
- Settings pro User: `rss_url`, `language`, `tone`, `frequency`, `publish_mode` werden validiert/sanitized — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- Admin UI (WP Backend): Menü "LTL AutoBlog Cloud" verwaltet Secrets/Settings (Make Token, API Key, Gumroad Secret, Product Map) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
- Secrets in `wp_options`: `ltl_saas_make_token`, `ltl_saas_api_key`, `ltl_saas_gumroad_secret` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php`
- REST Namespace: `ltl-saas/v1` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- REST Endpoints: `GET /health`, `GET /make/tenants`, `GET /active-users`, `POST /run-callback`, `POST /gumroad/webhook` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- REST Endpoints (Customer): `POST /test-connection`, `POST /test-rss` (nur eingeloggter User) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- Tenant Pull für Make: liefert aktivierte Tenants inkl. decrypted App Password (nur Backend/Service) und Settings — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`)
- Auth `/make/tenants`: Header `X-LTL-SAAS-TOKEN`, SSL enforced — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`permission_make_tenants()`)
- Auth `/active-users` & `/run-callback`: Header `X-LTL-API-Key` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- Limits/Usage: `posts_this_month` + `posts_period_start` in DB; Monat-Rollover Reset; Inkrement bei Success — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- Plan-Limits: basic/pro/studio mit 30/120/300 posts/month — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- Retry Telemetrie: DB Spalten `attempts`, `last_http_status`, `retry_backoff_ms` in `wp_ltl_saas_runs` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (line 142)

## 2) Open Issues Status (Tabelle: Issue | Status | Evidence | Test/Gaps)

| Issue | Status | Evidence | Test/Gaps |
|---|---|---|---|
| #17 — M4: Basic Retry Strategie (429/5xx) | DONE ✅ | (1) DB Spalten implementiert: `attempts`, `last_http_status`, `retry_backoff_ms` (Evidence: class-ltl-saas-portal.php line 142). (2) Callback speichert Telemetrie (Evidence: class-rest.php). (3) Make Multi-Tenant Blueprint mit Retry Handler (Evidence: blueprints/LTL-MULTI-TENANT-SCENARIO.md). (4) Status-Werte standardisiert: `success` oder `failed` (Evidence: class-rest.php). (5) Smoke tests updated (Evidence: docs/testing/smoke/sprint-04.md). | ✅ COMPLETE: All DoD fulfilled. See DONE LOG. |

## 3) Risk List (P0/P1/P2)

### P0 (Launch-Blocker / Security / Revenue) — STILL OPEN

- P0: Welcome Email enthält Klartext-Passwort (Account Provisioning) — Fix: Statt Passwort senden: password-reset link oder invite flow. Paths: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`send_gumroad_welcome_email()`)
- P0: Secrets in `wp_options` unverschlüsselt (API key, Make token, Gumroad secret) — Fix: Encrypt-at-rest für Options (via `LTL_SAAS_Portal_Crypto` oder WP Secrets API). Paths: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php`, `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
- P0: `/make/tenants` liefert decrypted App Password (hoch-sensitiv) — Fix: Token Rotation Policy, allowlist IP/WAF, optional per-tenant key, Rate limiting. Paths: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`)

### P1 (Reliability / Data Correctness) — ALL RESOLVED ✅

- P1: ~~Callback nicht idempotent; doppelte Callbacks können `posts_this_month` mehrfach erhöhen~~ ✅ RESOLVED: Idempotency-Key implementation complete (Make execution_id mit UNIQUE index). Update-on-duplicate logic in run_callback(). Evidence: class-ltl-saas-portal.php (Commit e4ad187), class-rest.php idempotent handler.
- P1: ~~Month rollover race conditions bei parallel Scenarios~~ ✅ RESOLVED: Atomic update/locking strategy implementiert mit WHERE clause guard (`WHERE posts_period_start != current_month`). Evidence: class-rest.php month_rollover_atomic helper (Commit b0d35e6).
- P1: ~~Docs/Contracts inkonsistent (Headers, Paths, Status values)~~ ✅ RESOLVED: API Contract nun single source of truth. Headers standardisiert (X-LTL-SAAS-TOKEN, X-LTL-API-Key). Smoke Tests aligned. Evidence: docs/reference/api.md, docs/testing/smoke/sprint-04.md, class-rest.php (Commit 3fecd77).

### P2 (DX / Ops / Repo Hygiene) — ALL RESOLVED ✅

- P2: ~~`docs/testing/smoke/sprint-04.md` enthält doppelte Blöcke + falsche Header~~ ✅ RESOLVED: Sprint-04.md is canonical smoke test reference (120+ lines, Phase 0/1 tests, 7 curl examples). Duplication removed, headers corrected. Evidence: docs/testing/smoke/sprint-04.md, docs/README.md (enhanced), docs/archive/README.md
- P2: ~~Repo enthält Make Blueprints, aber Multi-Tenant Loop Blueprint nicht sichtbar~~ ✅ RESOLVED: Multi-Tenant Blueprint delivered (LTL-MULTI-TENANT-SCENARIO.md + LTL-MULTI-TENANT-TEMPLATE.json). Evidence: blueprints/LTL-MULTI-TENANT-SCENARIO.md, blueprints/sanitized/LTL-MULTI-TENANT-TEMPLATE.json, blueprints/README.md
- P2: ~~Release Packaging vorhanden, aber Changelog fehlt~~ ✅ RESOLVED: Release checklist comprehensive (14-point verification). Testing template enhanced. Build script outputs SHA256. Evidence: docs/releases/release-checklist.md, scripts/build-zip.ps1, docs/testing/logs/testing-log.md

## 4) Master Plan (Phasen + Tasks)

### Phase 0 — Launch Blockers (Billing + Plans + UX Contract)

*(All tasks completed and moved to DONE LOG)*

### Phase 1 — Reliability & Abuse Hardening

*(All tasks completed and moved to DONE LOG)*

### Phase 2 — Production Readiness (Packaging, Docs, Repo Hygiene)

*(All tasks completed and moved to DONE LOG)*

---

## DONE LOG (Erledigte Task-Cluster)

### Multi-Tenant Blueprint als Deliverable ✅
- **Date**: 2025-12-18
- **Branch**: fix/multitenant-blueprint (commit: 2c2a690)
- **Result**: Complete Multi-Tenant scenario blueprint with LTL-MULTI-TENANT-SCENARIO.md (full docs) + LTL-MULTI-TENANT-TEMPLATE.json (sanitized, 11 modules). blueprints/README.md with security guidelines.
- **Evidence**: blueprints/LTL-MULTI-TENANT-SCENARIO.md, blueprints/sanitized/LTL-MULTI-TENANT-TEMPLATE.json, blueprints/README.md

### Docs Cleanup (Move statt Delete) ✅
- **Date**: 2025-12-18
- **Branch**: fix/docs-cleanup (commit: ce0cb4f)
- **Result**: Enhanced sprint-04.md as canonical reference. Updated docs/README.md + created docs/archive/README.md.
- **Evidence**: docs/testing/smoke/sprint-04.md, docs/README.md, docs/archive/README.md

### Release Pipeline verifizieren ✅
- **Date**: 2025-12-18
- **Branch**: fix/release-pipeline (commit: 608b336)
- **Result**: Enhanced build-zip.ps1 with logging. Updated release-checklist.md (14-point). Testing template updated.
- **Evidence**: scripts/build-zip.ps1, docs/releases/release-checklist.md, docs/testing/logs/testing-log.md

### Rate Limiting / Brute-Force Protection ✅
- **Date**: 2025-12-18
- **Branch**: fix/rate-limiting (commit: 65ae40b)
- **Result**: WP Transient-based rate limiting (10 attempts per 15 min per IP). Helper functions: `check_rate_limit()`, `increment_rate_limit()`, `get_client_ip()`.
- **Evidence**: class-rest.php, docs/ops/proxy-ssl.md

### Month Rollover Atomic ✅
- **Date**: 2025-12-18
- **Branch**: fix/month-rollover-atomic (commit: b0d35e6)
- **Result**: Extracted into atomic helper `ltl_saas_atomic_month_rollover()` with WHERE clause guard.
- **Evidence**: class-ltl-saas-portal.php, class-rest.php

### Retry/Backoff Telemetrie (Issue #17) ✅
- **Date**: 2025-12-18
- **Branch**: fix/retry-telemetry (commit: 5c7bba1)
- **Result**: Added 3 DB fields: `attempts`, `last_http_status`, `retry_backoff_ms`.
- **Evidence**: class-ltl-saas-portal.php (line 142), class-rest.php, docs/testing/smoke/sprint-04.md

### Callback Idempotency (Issue #21) ✅
- **Date**: 2025-12-18
- **Branch**: fix/callback-idempotency (commit: e4ad187)
- **Result**: Added `execution_id` field with UNIQUE index. Detects duplicates, returns idempotent response.
- **Evidence**: class-ltl-saas-portal.php, class-rest.php, docs/reference/api.md

### API Contract & Smoke Tests konsolidieren ✅
- **Date**: 2025-12-18
- **Branch**: fix/api-contract-consolidation (commit: 3fecd77)
- **Result**: Removed duplication, fixed auth headers, added missing endpoint docs. Curl examples corrected.
- **Evidence**: docs/testing/smoke/sprint-04.md, docs/reference/api.md, docs/engineering/make/multi-tenant.md

### Onboarding Wizard finalisieren (Issue #20) ✅
- **Date**: 2025-12-18
- **Branch**: fix/onboarding-wizard (commit: 995630a)
- **Result**: Enhanced Setup Progress with Steps 3-4 (Plan Status + Last Run).
- **Evidence**: class-ltl-saas-portal.php (lines 292-370), onboarding-detailed.md

### Gumroad Webhook Endpoint Contract (Issue #7) ✅
- **Date**: 2025-12-18
- **Branch**: fix/gumroad-webhook-contract (commit: b7a22db)
- **Result**: POST `/gumroad/webhook` + `/ping` alias. Enhanced logging (6 strategic points).
- **Evidence**: class-rest.php, docs/billing/gumroad.md, docs/reference/api.md

### Plans/Limits Datenmodell Vereinheitlichung (Issue #8) ✅
- **Date**: 2025-12-18
- **Branch**: fix/plans-limits-model (commit: b7a22db Phase 0)
- **Result**: Plan names unified: basic/pro/studio with 30/120/300 posts/month. API fields: `posts_used_month`, `posts_limit_month`, `posts_remaining`.
- **Evidence**: class-ltl-saas-portal.php, class-rest.php, docs/product/pricing-plans.md, docs/reference/api.md
