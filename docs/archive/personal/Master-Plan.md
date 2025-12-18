# Master Plan Ã¢â‚¬â€ LTL AutoBlog Cloud (V1 Launch)

## 1) Current State Snapshot (max 20 bullets, jeder Bullet mit Evidence-Pfad)

- WordPress-Plugin Entry + Hooks: Plugin lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤dt `LTL_SAAS_Portal::instance()`, registriert Activation/Deactivation Hooks (DB Setup) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/ltl-saas-portal.php`
- Haupt-Komponente initialisiert Admin + REST + Shortcodes Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`LTL_SAAS_Portal::init()`)
- Customer UI lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤uft ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ber Shortcode `[ltl_saas_dashboard]` inkl. Login-Gate Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`shortcode_dashboard()`)
- In `shortcode_dashboard()` existiert ein Setup-Progress Block (Step 1: WP verbinden, Step 2: RSS + Settings) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (HTML Block ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾Dein Setup-FortschrittÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“)
- Persistenz: Plugin erstellt drei DB Tabellen: `wp_ltl_saas_connections`, `wp_ltl_saas_settings`, `wp_ltl_saas_runs` (Prefix abhÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤ngig) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`LTL_SAAS_Portal::activate()` / `CREATE TABLE`)
- WP-Connection wird pro User gespeichert: `wp_url`, `wp_user`, `wp_app_password_enc` (verschlÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼sselt) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (Insert/Update in `ltl_saas_connections`)
- VerschlÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼sselung at-rest fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r App Password: AES-256-CBC + HMAC (v1 Format), Keys aus WordPress Salts (`AUTH_KEY`, `SECURE_AUTH_KEY`) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-crypto.php` (`LTL_SAAS_Portal_Crypto::encrypt/decrypt`)
- Settings pro User: `rss_url`, `language`, `tone`, `frequency`, `publish_mode` werden per Form + Nonce validiert/sanitized Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`wp_verify_nonce`, `esc_url_raw`, `in_array`-Checks)
- Admin UI (WP Backend) existiert unter MenÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾LTL AutoBlog CloudÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ und verwaltet Secrets/Settings (Make Token, API Key, Gumroad Secret, Product Map, Checkout URLs) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
- Secrets werden in `wp_options` gespeichert (z.B. `ltl_saas_make_token`, `ltl_saas_api_key`, `ltl_saas_gumroad_secret`) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php` (`get_option/update_option`)
- REST Namespace ist `ltl-saas/v1` Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`const NAMESPACE`)
- REST Endpoints (Portal ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Health & Make): `GET /health`, `GET /make/tenants`, `GET /active-users` Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`register_routes()`)
- REST Endpoints (Callbacks/Billing): `POST /run-callback`, `POST /gumroad/webhook` (+ legacy `/ping` alias) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`register_routes()`, `gumroad_webhook()`)
- REST Endpoints (Customer-UX): `POST /test-connection`, `POST /test-rss` (nur eingeloggter User) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`permission_user_logged_in`, `test_wp_connection`, `test_rss_feed`)
- Tenant Pull fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r Make liefert aktivierte Tenants inkl. decrypted App Password (nur Backend/Service) und Settings + Usage Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`)
- Auth fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r `GET /make/tenants`: Header `X-LTL-SAAS-TOKEN`, SSL enforced Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`permission_make_tenants()`)
- Auth fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r `GET /active-users` & `POST /run-callback`: Header `X-LTL-API-Key` (Vergleich gegen Option) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_active_users()`, `run_callback()`)
- Limits/Usage: `posts_this_month` + `posts_period_start` in `wp_ltl_saas_settings`; Monat-Rollover Reset in `/make/tenants`; Inkrement bei `run_callback(status=success)` Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`, `run_callback()`)
- Plan-Limits jetzt einheitlich: Code-Map (basic/pro/studio) mit Limits (30/120/300) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`ltl_saas_plan_posts_limit`, `ltl_saas_get_tenant_state`)

## 2) Open Issues Status (Tabelle: Issue | Status | Evidence | Test/Gaps)

| Issue | Status | Evidence | Test/Gaps |
|---|---|---|---|
| #17 Ã¢â‚¬â€ M4: Basic Retry Strategie (429/5xx) | PARTIAL | Retry-Konzept dokumentiert: `docs/engineering/make/retry-strategy.md`. Smoke Tests: `docs/testing/smoke/issue-17.md`. Callback speichert `raw_payload` und kann retry-Metadaten transportieren (ohne Spalten) Ã¢â‚¬â€ Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`run_callback()` erzeugt `raw_payload`) | **Gaps:** (1) Keine Make-Multi-Tenant Blueprint-Datei im Repo, die die Retry-Handler tatsÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤chlich implementiert (nur Docs). (2) DB-Spalten `attempts/last_http_status/retry_backoff_ms` existieren nicht in `wp_ltl_saas_runs` (nur Doc-Vorschlag). (3) `status` Werte sind inkonsistent ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ber Docs (`success/failed` vs `success/error`). |



## 3) Risk List (P0/P1/P2, jeweils konkrete Fix-Idee + Pfade)

### P0 (Launch-Blocker / Security / Revenue)


- P0: Welcome Email enthÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤lt Klartext-Passwort (Account Provisioning) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: statt Passwort senden: `wp_set_password` vermeiden / ausschlieÃƒÆ’Ã†â€™Ãƒâ€¦Ã‚Â¸lich Password-Reset-Link, oder Invite Flow; Audit Trail Ã¢â‚¬â€ Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`send_gumroad_welcome_email()`)
- P0: Secrets in `wp_options` unverschlÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼sselt (API key, Make token, Gumroad secret) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: Encrypt-at-rest fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r Options (z.B. via `LTL_SAAS_Portal_Crypto` oder WP Secrets API/Env); zusÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤tzlich Hardening + minimaler Scope Ã¢â‚¬â€ Pfade: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php`, `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
- P0: `/make/tenants` liefert decrypted App Password (hoch-sensitiv); Abuse surface bei Token-Leak ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: Token Rotation Policy, allowlist IP/Basic WAF, optional per-tenant key, Logging/Rate limit; ggf. alternative: Make zieht App Password nur on-demand Ã¢â‚¬â€ Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`, `permission_make_tenants()`), `docs/engineering/make/multi-tenant.md`

### P1 (Reliability / Data Correctness)

- P1: Callback ist nicht idempotent; doppelte Callbacks kÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nnen `posts_this_month` mehrfach erhÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶hen ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: Idempotency-Key (Make execution id) persistieren und unique enforce; Update statt Insert bei Duplikat Ã¢â‚¬â€ Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`run_callback()`), DB Schema in `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- P1: Month rollover reset findet sowohl in `/make/tenants` als auch `run_callback` statt; mÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶gliche Race Conditions bei parallel laufenden Scenarios ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: atomarer Update/Locking Strategy (z.B. `UPDATE ... WHERE posts_period_start != current_month`) Ã¢â‚¬â€ Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- P1: Docs/Contracts inkonsistent (Headers, Paths, Status values) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: API Contract ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾single source of truthÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ + Smoke Tests alignen Ã¢â‚¬â€ Pfade: `docs/reference/api.md`, `docs/testing/smoke/sprint-04.md`, `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`

### P2 (DX / Ops / Repo Hygiene)

- P2: `docs/testing/smoke/sprint-04.md` enthÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤lt doppelte BlÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶cke + falsche Header/Auth Beispiele (Bearer vs `X-LTL-SAAS-TOKEN`) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: Doc cleanup (prefer MOVE nach `docs/archive/` statt delete) Ã¢â‚¬â€ Pfade: `docs/testing/smoke/sprint-04.md`, `docs/archive/`
- P2: Repo enthÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤lt Make Blueprints, aber Multi-Tenant Loop Blueprint ist nicht sichtbar/ableitbar (nur generische Bot Blueprints) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: Multi-Tenant Blueprint export + sanitize pipeline nutzen Ã¢â‚¬â€ Pfade: `blueprints/sanitized/**`, `scripts/sanitize_make_blueprints.py`, `docs/engineering/make/multi-tenant.md`
- P2: Release Packaging ist vorhanden, aber Changelog fehlt als Artefakt (Checklist referenziert) ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ Fix: schlanker `CHANGELOG.md` oder Release Notes Prozess (falls gewÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nscht) Ã¢â‚¬â€ Pfade: `scripts/build-zip.ps1`, `docs/releases/release-checklist.md`

## 4) Master Plan (Phasen + Tasks)

### Phase 0 Ã¢â‚¬â€ Launch Blockers (Billing + Plans + UX Contract)


Task: Onboarding Wizard finalisieren (Issue #20)
- Goal: Neukunde kommt ohne Support ans Ziel (Connect WP ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ RSS ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ First Run + Plan Status).
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`, `docs/product/onboarding.md`, `docs/product/onboarding-detailed.md`
- DoD: (1) UI enthÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤lt klare Schritt-fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r-Schritt Hinweise + Link zu Onboarding Doc. (2) Setup Progress umfasst mindestens: Connection OK, RSS OK, Plan aktiv, letzter Run. (3) Smoke-Test-Anleitung im Onboarding ist mit UI deckungsgleich.
- Impact: High
- KomplexitÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤t: M

Task: API Contract & Smoke Tests konsolidieren
- Goal: Keine widersprÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼chlichen Header/Paths/Status ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ber Docs.
- Files to touch: `docs/reference/api.md`, `docs/reference/architecture.md`, `docs/testing/smoke/sprint-04.md`, `docs/engineering/make/multi-tenant.md`
- DoD: (1) Alle Beispiele nutzen echte Header (`X-LTL-SAAS-TOKEN`, `X-LTL-API-Key`). (2) Pfade stimmen mit `register_routes()` ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼berein. (3) Statuswerte (`success`/`error`) sind konsistent.
- Impact: High
- KomplexitÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤t: S

### Phase 1 Ã¢â‚¬â€ Reliability & Abuse Hardening

Task: Callback Idempotency
- Goal: Doppelte Callbacks dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rfen Usage nicht doppelt zÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤hlen.
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- DoD: (1) Callback akzeptiert `run_id`/`execution_id` und enforced uniqueness. (2) Usage increment ist idempotent. (3) Regression: Unknown tenant wird weiter abgewiesen.
- Impact: High
- KomplexitÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤t: M

Task: Retry/Backoff Telemetrie (Issue #17)
- Goal: Nach Retry sauber loggen (mindestens in `raw_payload`, optional Spalten).
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`, `docs/engineering/make/retry-strategy.md`, `docs/testing/smoke/issue-17.md`
- DoD: (1) Make sendet `attempts/last_http_status/retry_backoff_ms` im Callback. (2) Portal speichert diese zuverlÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤ssig (Spalten oder JSON). (3) Smoke Tests Issue 17 sind ausfÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼hrbar.
- Impact: Med
- KomplexitÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤t: M

Task: Month rollover atomic machen
- Goal: Kein Race bei parallelen Runs/Tenant Pulls.
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- DoD: Reset+Increment erfolgen atomar und reproduzierbar; dokumentierte Testcases `docs/testing/smoke/sprint-04.md` passen.
- Impact: Med
- KomplexitÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤t: S/M

Task: Rate limiting / brute-force surface reduzieren
- Goal: Schutz gegen API-Key/Token brute-force und DoS.
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `docs/ops/proxy-ssl.md` (falls WAF/Proxy genutzt)
- DoD: Baseline-Schutz (z.B. IP allowlist am Reverse Proxy, oder WP transient based throttling) + Logging.
- Impact: Med
- KomplexitÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¤t: M

### Phase 2 Ã¢â‚¬â€ Production Readiness (Packaging, Docs, Repo Hygiene)

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
### #20 — Onboarding Wizard finalisieren ✅
- **Date**: 2025-12-18
- **Branch**: `fix/onboarding-wizard` (commit: 995630a)
- **Result**: Enhanced Setup Progress with Steps 3-4 (Plan Status + Last Run) + doc link. Step 3 queries `ltl_saas_get_tenant_state()` for plan display, Step 4 queries `wp_ltl_saas_runs` table for last execution info.
- **Impact**: P0 — Launch Blocker resolved
- **Evidence**: class-ltl-saas-portal.php (lines 292-370, 73+ lines added), onboarding-detailed.md (linked from header)
### Issue #7 Ã¢â‚¬â€ Gumroad Webhook Endpoint Contract ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦
- **Date**: 2025-12-18
- **Branch**: `fix/gumroad-webhook-contract` (commit: b7a22db)
- **Result**: POST `/gumroad/webhook` + `/ping` alias implemented, logging enhanced (6 strategic points), docs updated (billing + API reference + smoke tests)
- **Impact**: P0 Launch Blocker resolved

### Issue #8 Ã¢â‚¬â€ Plans/Limits Datenmodell Vereinheitlichung ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦
- **Date**: 2025-12-18
- **Branch**: `fix/plans-limits-model` (current)
- **Result**: Plan names unified to `basic/pro/studio` with limits `30/120/300` posts/month. API response fields clarified: `posts_used_month` + `posts_limit_month` + `posts_remaining`. Pricing docs finalized. API Reference updated.
- **Impact**: P0 Launch Blocker resolved
- **Files Changed**:
  - `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (plan helpers + tenant state)
  - `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (make/tenants response)
  - `docs/product/pricing-plans.md` (finalized plan structure)
  - `docs/reference/api.md` (Issue #8 endpoint spec)