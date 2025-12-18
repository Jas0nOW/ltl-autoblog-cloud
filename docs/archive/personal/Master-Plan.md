# Master Plan — LTL AutoBlog Cloud (V1 Launch)

## 1) Current State Snapshot (max 20 bullets, jeder Bullet mit Evidence-Pfad)

- WordPress-Plugin Entry + Hooks: Plugin lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤dt `LTL_SAAS_Portal::instance()`, registriert Activation/Deactivation Hooks (DB Setup) — Evidence: `wp-portal-plugin/ltl-saas-portal/ltl-saas-portal.php`
- Haupt-Komponente initialisiert Admin + REST + Shortcodes — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`LTL_SAAS_Portal::init()`)
- Customer UI lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤uft ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ber Shortcode `[ltl_saas_dashboard]` inkl. Login-Gate — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`shortcode_dashboard()`)
- In `shortcode_dashboard()` existiert ein Setup-Progress Block (Step 1: WP verbinden, Step 2: RSS + Settings) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (HTML Block ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾Dein Setup-FortschrittÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“)
- Persistenz: Plugin erstellt drei DB Tabellen: `wp_ltl_saas_connections`, `wp_ltl_saas_settings`, `wp_ltl_saas_runs` (Prefix abhÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤ngig) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`LTL_SAAS_Portal::activate()` / `CREATE TABLE`)
- WP-Connection wird pro User gespeichert: `wp_url`, `wp_user`, `wp_app_password_enc` (verschlÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼sselt) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (Insert/Update in `ltl_saas_connections`)
- VerschlÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼sselung at-rest fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r App Password: AES-256-CBC + HMAC (v1 Format), Keys aus WordPress Salts (`AUTH_KEY`, `SECURE_AUTH_KEY`) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-crypto.php` (`LTL_SAAS_Portal_Crypto::encrypt/decrypt`)
- Settings pro User: `rss_url`, `language`, `tone`, `frequency`, `publish_mode` werden per Form + Nonce validiert/sanitized — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`wp_verify_nonce`, `esc_url_raw`, `in_array`-Checks)
- Admin UI (WP Backend) existiert unter MenÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾LTL AutoBlog CloudÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ und verwaltet Secrets/Settings (Make Token, API Key, Gumroad Secret, Product Map, Checkout URLs) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
- Secrets werden in `wp_options` gespeichert (z.B. `ltl_saas_make_token`, `ltl_saas_api_key`, `ltl_saas_gumroad_secret`) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php` (`get_option/update_option`)
- REST Namespace ist `ltl-saas/v1` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`const NAMESPACE`)
- REST Endpoints (Portal ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Health & Make): `GET /health`, `GET /make/tenants`, `GET /active-users` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`register_routes()`)
- REST Endpoints (Callbacks/Billing): `POST /run-callback`, `POST /gumroad/webhook` (+ legacy `/ping` alias) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`register_routes()`, `gumroad_webhook()`)
- REST Endpoints (Customer-UX): `POST /test-connection`, `POST /test-rss` (nur eingeloggter User) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`permission_user_logged_in`, `test_wp_connection`, `test_rss_feed`)
- Tenant Pull fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r Make liefert aktivierte Tenants inkl. decrypted App Password (nur Backend/Service) und Settings + Usage — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`)
- Auth fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r `GET /make/tenants`: Header `X-LTL-SAAS-TOKEN`, SSL enforced — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`permission_make_tenants()`)
- Auth fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r `GET /active-users` & `POST /run-callback`: Header `X-LTL-API-Key` (Vergleich gegen Option) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_active_users()`, `run_callback()`)
- Limits/Usage: `posts_this_month` + `posts_period_start` in `wp_ltl_saas_settings`; Monat-Rollover Reset in `/make/tenants`; Inkrement bei `run_callback(status=success)` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`, `run_callback()`)
- Plan-Limits jetzt einheitlich: Code-Map (basic/pro/studio) mit Limits (30/120/300) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`ltl_saas_plan_posts_limit`, `ltl_saas_get_tenant_state`)

## 2) Open Issues Status (Tabelle: Issue | Status | Evidence | Test/Gaps)

| Issue | Status | Evidence | Test/Gaps |
|---|---|---|---|
| #17 — M4: Basic Retry Strategie (429/5xx) | PARTIAL | Retry-Konzept dokumentiert: `docs/engineering/make/retry-strategy.md`. Smoke Tests: `docs/testing/smoke/issue-17.md`. Callback speichert `raw_payload` und kann retry-Metadaten transportieren (ohne Spalten) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`run_callback()` erzeugt `raw_payload`) | **Gaps:** (1) Keine Make-Multi-Tenant Blueprint-Datei im Repo, die die Retry-Handler tatsÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤chlich implementiert (nur Docs). (2) DB-Spalten `attempts/last_http_status/retry_backoff_ms` existieren nicht in `wp_ltl_saas_runs` (nur Doc-Vorschlag). (3) `status` Werte sind inkonsistent ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ber Docs (`success/failed` vs `success/error`). |



## 3) Risk List (P0/P1/P2, jeweils konkrete Fix-Idee + Pfade)

### P0 (Launch-Blocker / Security / Revenue)


- P0: Welcome Email enthÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤lt Klartext-Passwort (Account Provisioning) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: statt Passwort senden: `wp_set_password` vermeiden / ausschlieÃƒÆ’Ã†â€™Ãƒâ€¦Ã‚Â¸lich Password-Reset-Link, oder Invite Flow; Audit Trail — Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`send_gumroad_welcome_email()`)
- P0: Secrets in `wp_options` unverschlÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼sselt (API key, Make token, Gumroad secret) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: Encrypt-at-rest fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r Options (z.B. via `LTL_SAAS_Portal_Crypto` oder WP Secrets API/Env); zusÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤tzlich Hardening + minimaler Scope — Pfade: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php`, `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
- P0: `/make/tenants` liefert decrypted App Password (hoch-sensitiv); Abuse surface bei Token-Leak ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: Token Rotation Policy, allowlist IP/Basic WAF, optional per-tenant key, Logging/Rate limit; ggf. alternative: Make zieht App Password nur on-demand — Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`, `permission_make_tenants()`), `docs/engineering/make/multi-tenant.md`

### P1 (Reliability / Data Correctness)

- P1: Callback ist nicht idempotent; doppelte Callbacks kÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nnen `posts_this_month` mehrfach erhÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶hen ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: Idempotency-Key (Make execution id) persistieren und unique enforce; Update statt Insert bei Duplikat — Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`run_callback()`), DB Schema in `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- P1: Month rollover reset findet sowohl in `/make/tenants` als auch `run_callback` statt; mÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶gliche Race Conditions bei parallel laufenden Scenarios ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: atomarer Update/Locking Strategy (z.B. `UPDATE ... WHERE posts_period_start != current_month`) — Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- P1: Docs/Contracts inkonsistent (Headers, Paths, Status values) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: API Contract ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾single source of truthÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ + Smoke Tests alignen — Pfade: `docs/reference/api.md`, `docs/testing/smoke/sprint-04.md`, `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`

### P2 (DX / Ops / Repo Hygiene)

- P2: `docs/testing/smoke/sprint-04.md` enthÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤lt doppelte BlÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶cke + falsche Header/Auth Beispiele (Bearer vs `X-LTL-SAAS-TOKEN`) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: Doc cleanup (prefer MOVE nach `docs/archive/` statt delete) — Pfade: `docs/testing/smoke/sprint-04.md`, `docs/archive/`
- P2: Repo enthÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤lt Make Blueprints, aber Multi-Tenant Loop Blueprint ist nicht sichtbar/ableitbar (nur generische Bot Blueprints) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: Multi-Tenant Blueprint export + sanitize pipeline nutzen — Pfade: `blueprints/sanitized/**`, `scripts/sanitize_make_blueprints.py`, `docs/engineering/make/multi-tenant.md`
- P2: Release Packaging ist vorhanden, aber Changelog fehlt als Artefakt (Checklist referenziert) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: schlanker `CHANGELOG.md` oder Release Notes Prozess (falls gewÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nscht) — Pfade: `scripts/build-zip.ps1`, `docs/releases/release-checklist.md`

## 4) Master Plan (Phasen + Tasks)

### Phase 0 — Launch Blockers (Billing + Plans + UX Contract)


Task: Onboarding Wizard finalisieren (Issue #20)
- Goal: Neukunde kommt ohne Support ans Ziel (Connect WP ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ RSS ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ First Run + Plan Status).
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`, `docs/product/onboarding.md`, `docs/product/onboarding-detailed.md`
- DoD: (1) UI enthÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤lt klare Schritt-fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r-Schritt Hinweise + Link zu Onboarding Doc. (2) Setup Progress umfasst mindestens: Connection OK, RSS OK, Plan aktiv, letzter Run. (3) Smoke-Test-Anleitung im Onboarding ist mit UI deckungsgleich.
- Impact: High
- KomplexitÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤t: M


### Phase 1 — Reliability & Abuse Hardening





### Phase 2 — Production Readiness (Packaging, Docs, Repo Hygiene)

Task: Release Pipeline verifizieren
- Goal: Reproduzierbares Plugin ZIP + Hashes, klare Release Steps.
- Files to touch: `scripts/build-zip.ps1`, `docs/releases/release-checklist.md`, `docs/testing/logs/testing-log.md`
- DoD: `build-zip.ps1` erzeugt ZIP + SHA256; Smoke Tests protokolliert; Checklist ist vollstÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤ndig.
- Impact: Med
- KomplexitÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤t: S

Task: Docs Cleanup (MOVE statt Delete)
- Goal: Doppelte/obsolete Docs in `docs/archive/` verschieben, aktive Docs konsistent.
- Files to touch: `docs/testing/smoke/sprint-04.md`, `docs/archive/**`, `docs/README.md`
- DoD: Keine doppelten Sektionen; Examples stimmen; Archiv enthÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤lt alte Varianten; `docs/README.md` bleibt Einstiegspunkt.
- Impact: Low/Med
- KomplexitÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤t: S

Task: Multi-Tenant Blueprint als Deliverable
- Goal: Kunden-/Team-Deliverable: tatsÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤chliches Multi-Tenant Make Scenario als sanitized blueprint.
- Files to touch: `blueprints/**`, `scripts/sanitize_make_blueprints.py`, `docs/engineering/make/multi-tenant.md`
- DoD: Blueprint enthÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤lt Module fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r `/make/tenants` Pull + Iterator + WP Post + `/run-callback` inkl. Retry Handler; Sanitizer entfernt Secrets.
- Impact: Med
- KomplexitÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤t: L
---

## DONE LOG (Erledigte Task-Cluster mit PR-Links)

### Rate Limiting / Brute-Force Protection (Issue #23) ✅
- **Date**: 2025-12-18
- **Branch**: `fix/rate-limiting` (commit: 65ae40b)
- **Result**: WP Transient-based rate limiting implemented (10 failed auth attempts per 15-minute window per IP). Protects `/run-callback` and `/make/tenants` endpoints. Helper functions added: `check_rate_limit()`, `increment_rate_limit()`, `get_client_ip()` (supports X-Forwarded-For for proxy setups). Logging on activation ("Rate limit exceeded: IP=..., endpoint=..., attempts=...").
- **Impact**: Phase 1 — Security & Abuse Hardening (prevents brute-force attacks, returns HTTP 429)
- **Evidence**: class-rest.php (3 helper functions + endpoint checks), docs/ops/proxy-ssl.md (Rate Limiting section with examples)

### Month Rollover Atomic ✅
- **Date**: 2025-12-18
- **Branch**: `fix/month-rollover-atomic` (commit: b0d35e6)
- **Result**: Extracted month rollover into atomic helper function `ltl_saas_atomic_month_rollover()` using WHERE clause guard. Prevents race conditions when parallel /make/tenants and run_callback requests both try to reset. Both endpoints now use single atomic query.
- **Impact**: Phase 1 — Data Correctness (prevents race conditions on month boundaries)
- **Evidence**: class-ltl-saas-portal.php (new helper function), class-rest.php (both get_make_tenants and run_callback refactored to use atomic helper)

### Retry/Backoff Telemetrie (Issue #17) ✅
- **Date**: 2025-12-18
- **Branch**: `fix/retry-telemetry` (commit: 5c7bba1)
- **Result**: Added 3 telemetry fields to `wp_ltl_saas_runs` table (attempts, last_http_status, retry_backoff_ms). Callback now accepts and stores retry metadata. Logging added to debug.log when attempts > 1.
- **Impact**: Phase 1 — Debugging & Observability (enables retry analysis)
- **Evidence**: class-ltl-saas-portal.php (DB schema + 3 columns), class-rest.php (telemetry extraction), issue-17.md (implementation marked complete), api.md (fields documented)

### Callback Idempotency ✅
- **Date**: 2025-12-18
- **Branch**: `fix/callback-idempotency` (commit: e4ad187)
- **Result**: Added `execution_id` field to runs table with UNIQUE index. Callback now detects duplicate execution IDs and returns idempotent response without double-incrementing usage. Backward compatible (execution_id optional).
- **Impact**: Phase 1 — Reliability (prevents duplicate usage counting on retries)
- **Evidence**: class-ltl-saas-portal.php (DB schema update), class-rest.php (idempotency logic), api.md (execution_id documented)

### API Contract & Smoke Tests konsolidieren ✅
- **Date**: 2025-12-18
- **Branch**: `fix/api-contract-consolidation` (commit: 3fecd77)
- **Result**: Removed duplication from sprint-04.md, fixed auth headers (X-LTL-SAAS-TOKEN, X-LTL-API-Key), added missing endpoint docs (/active-users, /test-connection, /test-rss), enhanced curl examples with correct payloads.
- **Impact**: P0 — API Contract & Smoke Tests aligned (Cluster 2)
- **Evidence**: docs/testing/smoke/sprint-04.md (removed duplication + corrected headers), docs/reference/api.md (added 3 endpoints), docs/engineering/make/multi-tenant.md (verified headers)

### #20 — Onboarding Wizard finalisieren ✅
- **Date**: 2025-12-18
- **Branch**: `fix/onboarding-wizard` (commit: 995630a)
- **Result**: Enhanced Setup Progress with Steps 3-4 (Plan Status + Last Run) + doc link. Step 3 queries `ltl_saas_get_tenant_state()` for plan display, Step 4 queries `wp_ltl_saas_runs` table for last execution info.
- **Impact**: P0 — Launch Blocker resolved
- **Evidence**: class-ltl-saas-portal.php (lines 292-370, 73+ lines added), onboarding-detailed.md (linked from header)
### Issue #7 — Gumroad Webhook Endpoint Contract ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦
- **Date**: 2025-12-18
- **Branch**: `fix/gumroad-webhook-contract` (commit: b7a22db)
- **Result**: POST `/gumroad/webhook` + `/ping` alias implemented, logging enhanced (6 strategic points), docs updated (billing + API reference + smoke tests)
- **Impact**: P0 Launch Blocker resolved

### Issue #8 — Plans/Limits Datenmodell Vereinheitlichung ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦
- **Date**: 2025-12-18
- **Branch**: `fix/plans-limits-model` (current)
- **Result**: Plan names unified to `basic/pro/studio` with limits `30/120/300` posts/month. API response fields clarified: `posts_used_month` + `posts_limit_month` + `posts_remaining`. Pricing docs finalized. API Reference updated.
- **Impact**: P0 Launch Blocker resolved
- **Files Changed**:
  - `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (plan helpers + tenant state)
  - `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (make/tenants response)
  - `docs/product/pricing-plans.md` (finalized plan structure)
  - `docs/reference/api.md` (Issue #8 endpoint spec)