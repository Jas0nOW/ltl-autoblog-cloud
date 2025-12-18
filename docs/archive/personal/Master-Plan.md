# Master Plan â€” LTL AutoBlog Cloud (V1 Launch)

## 1) Current State Snapshot (max 20 bullets, jeder Bullet mit Evidence-Pfad)

- WordPress-Plugin Entry + Hooks: Plugin lÃ¤dt `LTL_SAAS_Portal::instance()`, registriert Activation/Deactivation Hooks (DB Setup) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/ltl-saas-portal.php`
- Haupt-Komponente initialisiert Admin + REST + Shortcodes â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`LTL_SAAS_Portal::init()`)
- Customer UI lÃ¤uft Ã¼ber Shortcode `[ltl_saas_dashboard]` inkl. Login-Gate â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`shortcode_dashboard()`)
- In `shortcode_dashboard()` existiert ein Setup-Progress Block (Step 1: WP verbinden, Step 2: RSS + Settings) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (HTML Block â€žDein Setup-Fortschrittâ€œ)
- Persistenz: Plugin erstellt drei DB Tabellen: `wp_ltl_saas_connections`, `wp_ltl_saas_settings`, `wp_ltl_saas_runs` (Prefix abhÃ¤ngig) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`LTL_SAAS_Portal::activate()` / `CREATE TABLE`)
- WP-Connection wird pro User gespeichert: `wp_url`, `wp_user`, `wp_app_password_enc` (verschlÃ¼sselt) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (Insert/Update in `ltl_saas_connections`)
- VerschlÃ¼sselung at-rest fÃ¼r App Password: AES-256-CBC + HMAC (v1 Format), Keys aus WordPress Salts (`AUTH_KEY`, `SECURE_AUTH_KEY`) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-crypto.php` (`LTL_SAAS_Portal_Crypto::encrypt/decrypt`)
- Settings pro User: `rss_url`, `language`, `tone`, `frequency`, `publish_mode` werden per Form + Nonce validiert/sanitized â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`wp_verify_nonce`, `esc_url_raw`, `in_array`-Checks)
- Admin UI (WP Backend) existiert unter MenÃ¼ â€žLTL AutoBlog Cloudâ€œ und verwaltet Secrets/Settings (Make Token, API Key, Gumroad Secret, Product Map, Checkout URLs) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
- Secrets werden in `wp_options` gespeichert (z.B. `ltl_saas_make_token`, `ltl_saas_api_key`, `ltl_saas_gumroad_secret`) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php` (`get_option/update_option`)
- REST Namespace ist `ltl-saas/v1` â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`const NAMESPACE`)
- REST Endpoints (Portal â†’ Health & Make): `GET /health`, `GET /make/tenants`, `GET /active-users` â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`register_routes()`)
- REST Endpoints (Callbacks/Billing): `POST /run-callback`, `POST /gumroad/webhook` (+ legacy `/ping` alias) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`register_routes()`, `gumroad_webhook()`)
- REST Endpoints (Customer-UX): `POST /test-connection`, `POST /test-rss` (nur eingeloggter User) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`permission_user_logged_in`, `test_wp_connection`, `test_rss_feed`)
- Tenant Pull fÃ¼r Make liefert aktivierte Tenants inkl. decrypted App Password (nur Backend/Service) und Settings + Usage â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`)
- Auth fÃ¼r `GET /make/tenants`: Header `X-LTL-SAAS-TOKEN`, SSL enforced â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`permission_make_tenants()`)
- Auth fÃ¼r `GET /active-users` & `POST /run-callback`: Header `X-LTL-API-Key` (Vergleich gegen Option) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_active_users()`, `run_callback()`)
- Limits/Usage: `posts_this_month` + `posts_period_start` in `wp_ltl_saas_settings`; Monat-Rollover Reset in `/make/tenants`; Inkrement bei `run_callback(status=success)` â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`, `run_callback()`)
- Plan-Limits jetzt einheitlich: Code-Map (basic/pro/studio) mit Limits (30/120/300) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`ltl_saas_plan_posts_limit`, `ltl_saas_get_tenant_state`)

## 2) Open Issues Status (Tabelle: Issue | Status | Evidence | Test/Gaps)

| Issue | Status | Evidence | Test/Gaps |
|---|---|---|---|
| #20 â€” M5: Onboarding Wizard (Connect WP â†’ RSS â†’ Start) | PARTIAL | Setup-Progress UI: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (Block â€žDein Setup-Fortschrittâ€œ). Test-Endpunkte: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`/test-connection`, `/test-rss`). Onboarding-Doc: `docs/product/onboarding-detailed.md` | **Gaps:** (1) Kein expliziter Link im UI zu `docs/product/onboarding.md`/`onboarding-detailed.md`. (2) Wizard deckt nur Step 1/2 ab; Plan-Status + â€žletzter Runâ€œ als Setup-Schritt nicht als Wizard-Status gefÃ¼hrt. (3) Kein â€žTest Run startenâ€œ Button/Action im Portal (Doc erwÃ¤hnt). |
| #17 â€” M4: Basic Retry Strategie (429/5xx) | PARTIAL | Retry-Konzept dokumentiert: `docs/engineering/make/retry-strategy.md`. Smoke Tests: `docs/testing/smoke/issue-17.md`. Callback speichert `raw_payload` und kann retry-Metadaten transportieren (ohne Spalten) â€” Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`run_callback()` erzeugt `raw_payload`) | **Gaps:** (1) Keine Make-Multi-Tenant Blueprint-Datei im Repo, die die Retry-Handler tatsÃ¤chlich implementiert (nur Docs). (2) DB-Spalten `attempts/last_http_status/retry_backoff_ms` existieren nicht in `wp_ltl_saas_runs` (nur Doc-Vorschlag). (3) `status` Werte sind inkonsistent Ã¼ber Docs (`success/failed` vs `success/error`). |
| #8  M1: Plans + Limits Datenmodell (Basic/Pro/Studio) |  DONE | Plan names unified: `basic/pro/studio` (30/120/300 posts/month). API: `posts_used_month`, `posts_limit_month`, `posts_remaining`. |  DoD met | **Gaps:** (1) Issue fordert explizite Felder `posts_limit_month`, `posts_used_month` (persistiert) â€“ aktuell derived (`posts_limit_month`) und `posts_this_month` (anderer Name). (2) Plan-Namen im Issue/Docs (`Basic/Pro/Studio`) weichen von Code (`free/starter/pro/agency`) ab; `docs/product/pricing-plans.md` weicht ebenfalls ab. (3) â€žUser hat Plan + Limit sichtbarâ€œ: im Customer Dashboard wird Plan/Limit aktuell nicht als klare UI angezeigt (nur Lock-Screen bei `is_active=0` + Runs-Tabelle). |


## 3) Risk List (P0/P1/P2, jeweils konkrete Fix-Idee + Pfade)

### P0 (Launch-Blocker / Security / Revenue)

- P0: âœ… **DONE** â€” Issue #7 Billing Endpoint finalisiert (Route `/gumroad/webhook` + `/ping` Backward-Compat, Logging enhanced, Docs updated)
- P0: Welcome Email enthÃ¤lt Klartext-Passwort (Account Provisioning) â†’ Fix: statt Passwort senden: `wp_set_password` vermeiden / ausschlieÃŸlich Password-Reset-Link, oder Invite Flow; Audit Trail â€” Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`send_gumroad_welcome_email()`)
- P0: Secrets in `wp_options` unverschlÃ¼sselt (API key, Make token, Gumroad secret) â†’ Fix: Encrypt-at-rest fÃ¼r Options (z.B. via `LTL_SAAS_Portal_Crypto` oder WP Secrets API/Env); zusÃ¤tzlich Hardening + minimaler Scope â€” Pfade: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php`, `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
- P0: `/make/tenants` liefert decrypted App Password (hoch-sensitiv); Abuse surface bei Token-Leak â†’ Fix: Token Rotation Policy, allowlist IP/Basic WAF, optional per-tenant key, Logging/Rate limit; ggf. alternative: Make zieht App Password nur on-demand â€” Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`, `permission_make_tenants()`), `docs/engineering/make/multi-tenant.md`

### P1 (Reliability / Data Correctness)

- P1: Callback ist nicht idempotent; doppelte Callbacks kÃ¶nnen `posts_this_month` mehrfach erhÃ¶hen â†’ Fix: Idempotency-Key (Make execution id) persistieren und unique enforce; Update statt Insert bei Duplikat â€” Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`run_callback()`), DB Schema in `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- P1: Month rollover reset findet sowohl in `/make/tenants` als auch `run_callback` statt; mÃ¶gliche Race Conditions bei parallel laufenden Scenarios â†’ Fix: atomarer Update/Locking Strategy (z.B. `UPDATE ... WHERE posts_period_start != current_month`) â€” Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- P1: Docs/Contracts inkonsistent (Headers, Paths, Status values) â†’ Fix: API Contract â€žsingle source of truthâ€œ + Smoke Tests alignen â€” Pfade: `docs/reference/api.md`, `docs/testing/smoke/sprint-04.md`, `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`

### P2 (DX / Ops / Repo Hygiene)

- P2: `docs/testing/smoke/sprint-04.md` enthÃ¤lt doppelte BlÃ¶cke + falsche Header/Auth Beispiele (Bearer vs `X-LTL-SAAS-TOKEN`) â†’ Fix: Doc cleanup (prefer MOVE nach `docs/archive/` statt delete) â€” Pfade: `docs/testing/smoke/sprint-04.md`, `docs/archive/`
- P2: Repo enthÃ¤lt Make Blueprints, aber Multi-Tenant Loop Blueprint ist nicht sichtbar/ableitbar (nur generische Bot Blueprints) â†’ Fix: Multi-Tenant Blueprint export + sanitize pipeline nutzen â€” Pfade: `blueprints/sanitized/**`, `scripts/sanitize_make_blueprints.py`, `docs/engineering/make/multi-tenant.md`
- P2: Release Packaging ist vorhanden, aber Changelog fehlt als Artefakt (Checklist referenziert) â†’ Fix: schlanker `CHANGELOG.md` oder Release Notes Prozess (falls gewÃ¼nscht) â€” Pfade: `scripts/build-zip.ps1`, `docs/releases/release-checklist.md`

## 4) Master Plan (Phasen + Tasks)

### Phase 0 â€” Launch Blockers (Billing + Plans + UX Contract)

âœ… **DONE** â€” Task: Billing Endpoint finalisieren (Issue #7) â€” Gumroad Webhook Contract implementiert

âœ… **DONE** â€” Task: Plans/Limits Datenmodell vereinheitlichen (Issue #8) â€” Plan names unified to basic/pro/studio, API fields clarified

Task: Onboarding Wizard finalisieren (Issue #20)
- Goal: Neukunde kommt ohne Support ans Ziel (Connect WP â†’ RSS â†’ First Run + Plan Status).
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`, `docs/product/onboarding.md`, `docs/product/onboarding-detailed.md`
- DoD: (1) UI enthÃ¤lt klare Schritt-fÃ¼r-Schritt Hinweise + Link zu Onboarding Doc. (2) Setup Progress umfasst mindestens: Connection OK, RSS OK, Plan aktiv, letzter Run. (3) Smoke-Test-Anleitung im Onboarding ist mit UI deckungsgleich.
- Impact: High
- KomplexitÃ¤t: M

Task: API Contract & Smoke Tests konsolidieren
- Goal: Keine widersprÃ¼chlichen Header/Paths/Status Ã¼ber Docs.
- Files to touch: `docs/reference/api.md`, `docs/reference/architecture.md`, `docs/testing/smoke/sprint-04.md`, `docs/engineering/make/multi-tenant.md`
- DoD: (1) Alle Beispiele nutzen echte Header (`X-LTL-SAAS-TOKEN`, `X-LTL-API-Key`). (2) Pfade stimmen mit `register_routes()` Ã¼berein. (3) Statuswerte (`success`/`error`) sind konsistent.
- Impact: High
- KomplexitÃ¤t: S

### Phase 1 â€” Reliability & Abuse Hardening

Task: Callback Idempotency
- Goal: Doppelte Callbacks dÃ¼rfen Usage nicht doppelt zÃ¤hlen.
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- DoD: (1) Callback akzeptiert `run_id`/`execution_id` und enforced uniqueness. (2) Usage increment ist idempotent. (3) Regression: Unknown tenant wird weiter abgewiesen.
- Impact: High
- KomplexitÃ¤t: M

Task: Retry/Backoff Telemetrie (Issue #17)
- Goal: Nach Retry sauber loggen (mindestens in `raw_payload`, optional Spalten).
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`, `docs/engineering/make/retry-strategy.md`, `docs/testing/smoke/issue-17.md`
- DoD: (1) Make sendet `attempts/last_http_status/retry_backoff_ms` im Callback. (2) Portal speichert diese zuverlÃ¤ssig (Spalten oder JSON). (3) Smoke Tests Issue 17 sind ausfÃ¼hrbar.
- Impact: Med
- KomplexitÃ¤t: M

Task: Month rollover atomic machen
- Goal: Kein Race bei parallelen Runs/Tenant Pulls.
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- DoD: Reset+Increment erfolgen atomar und reproduzierbar; dokumentierte Testcases `docs/testing/smoke/sprint-04.md` passen.
- Impact: Med
- KomplexitÃ¤t: S/M

Task: Rate limiting / brute-force surface reduzieren
- Goal: Schutz gegen API-Key/Token brute-force und DoS.
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `docs/ops/proxy-ssl.md` (falls WAF/Proxy genutzt)
- DoD: Baseline-Schutz (z.B. IP allowlist am Reverse Proxy, oder WP transient based throttling) + Logging.
- Impact: Med
- KomplexitÃ¤t: M

### Phase 2 â€” Production Readiness (Packaging, Docs, Repo Hygiene)

Task: Release Pipeline verifizieren
- Goal: Reproduzierbares Plugin ZIP + Hashes, klare Release Steps.
- Files to touch: `scripts/build-zip.ps1`, `docs/releases/release-checklist.md`, `docs/testing/logs/testing-log.md`
- DoD: `build-zip.ps1` erzeugt ZIP + SHA256; Smoke Tests protokolliert; Checklist ist vollstÃ¤ndig.
- Impact: Med
- KomplexitÃ¤t: S

Task: Docs Cleanup (MOVE statt Delete)
- Goal: Doppelte/obsolete Docs in `docs/archive/` verschieben, aktive Docs konsistent.
- Files to touch: `docs/testing/smoke/sprint-04.md`, `docs/archive/**`, `docs/README.md`
- DoD: Keine doppelten Sektionen; Examples stimmen; Archiv enthÃ¤lt alte Varianten; `docs/README.md` bleibt Einstiegspunkt.
- Impact: Low/Med
- KomplexitÃ¤t: S

Task: Multi-Tenant Blueprint als Deliverable
- Goal: Kunden-/Team-Deliverable: tatsÃ¤chliches Multi-Tenant Make Scenario als sanitized blueprint.
- Files to touch: `blueprints/**`, `scripts/sanitize_make_blueprints.py`, `docs/engineering/make/multi-tenant.md`
- DoD: Blueprint enthÃ¤lt Module fÃ¼r `/make/tenants` Pull + Iterator + WP Post + `/run-callback` inkl. Retry Handler; Sanitizer entfernt Secrets.
- Impact: Med
- KomplexitÃ¤t: L
---

## DONE LOG (Erledigte Task-Cluster mit PR-Links)

### Issue #7 â€” Gumroad Webhook Endpoint Contract âœ…
- **Date**: 2025-12-18
- **Branch**: `fix/gumroad-webhook-contract` (commit: b7a22db)
- **Result**: POST `/gumroad/webhook` + `/ping` alias implemented, logging enhanced (6 strategic points), docs updated (billing + API reference + smoke tests)
- **Impact**: P0 Launch Blocker resolved

### Issue #8 â€” Plans/Limits Datenmodell Vereinheitlichung âœ…
- **Date**: 2025-12-18
- **Branch**: `fix/plans-limits-model` (current)
- **Result**: Plan names unified to `basic/pro/studio` with limits `30/120/300` posts/month. API response fields clarified: `posts_used_month` + `posts_limit_month` + `posts_remaining`. Pricing docs finalized. API Reference updated.
- **Impact**: P0 Launch Blocker resolved
- **Files Changed**:
  - `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (plan helpers + tenant state)
  - `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (make/tenants response)
  - `docs/product/pricing-plans.md` (finalized plan structure)
  - `docs/reference/api.md` (Issue #8 endpoint spec)