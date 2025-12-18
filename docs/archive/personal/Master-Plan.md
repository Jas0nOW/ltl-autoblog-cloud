# Master Plan — LTL AutoBlog Cloud (V1 Launch)

## 1) Current State Snapshot (max 20 bullets, jeder Bullet mit Evidence-Pfad)

- WordPress-Plugin Entry + Hooks: Plugin lädt `LTL_SAAS_Portal::instance()`, registriert Activation/Deactivation Hooks (DB Setup) — Evidence: `wp-portal-plugin/ltl-saas-portal/ltl-saas-portal.php`
- Haupt-Komponente initialisiert Admin + REST + Shortcodes — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`LTL_SAAS_Portal::init()`)
- Customer UI läuft über Shortcode `[ltl_saas_dashboard]` inkl. Login-Gate — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`shortcode_dashboard()`)
- In `shortcode_dashboard()` existiert ein Setup-Progress Block (Step 1: WP verbinden, Step 2: RSS + Settings) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (HTML Block „Dein Setup-Fortschritt“)
- Persistenz: Plugin erstellt drei DB Tabellen: `wp_ltl_saas_connections`, `wp_ltl_saas_settings`, `wp_ltl_saas_runs` (Prefix abhängig) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`LTL_SAAS_Portal::activate()` / `CREATE TABLE`)
- WP-Connection wird pro User gespeichert: `wp_url`, `wp_user`, `wp_app_password_enc` (verschlüsselt) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (Insert/Update in `ltl_saas_connections`)
- Verschlüsselung at-rest für App Password: AES-256-CBC + HMAC (v1 Format), Keys aus WordPress Salts (`AUTH_KEY`, `SECURE_AUTH_KEY`) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-crypto.php` (`LTL_SAAS_Portal_Crypto::encrypt/decrypt`)
- Settings pro User: `rss_url`, `language`, `tone`, `frequency`, `publish_mode` werden per Form + Nonce validiert/sanitized — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`wp_verify_nonce`, `esc_url_raw`, `in_array`-Checks)
- Admin UI (WP Backend) existiert unter Menü „LTL AutoBlog Cloud“ und verwaltet Secrets/Settings (Make Token, API Key, Gumroad Secret, Product Map, Checkout URLs) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
- Secrets werden in `wp_options` gespeichert (z.B. `ltl_saas_make_token`, `ltl_saas_api_key`, `ltl_saas_gumroad_secret`) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php` (`get_option/update_option`)
- REST Namespace ist `ltl-saas/v1` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`const NAMESPACE`)
- REST Endpoints (Portal → Health & Make): `GET /health`, `GET /make/tenants`, `GET /active-users` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`register_routes()`)
- REST Endpoints (Callbacks/Billing): `POST /run-callback`, `POST /gumroad/ping` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`register_routes()`)
- REST Endpoints (Customer-UX): `POST /test-connection`, `POST /test-rss` (nur eingeloggter User) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`permission_user_logged_in`, `test_wp_connection`, `test_rss_feed`)
- Tenant Pull für Make liefert aktivierte Tenants inkl. decrypted App Password (nur Backend/Service) und Settings + Usage — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`)
- Auth für `GET /make/tenants`: Header `X-LTL-SAAS-TOKEN`, SSL enforced — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`permission_make_tenants()`)
- Auth für `GET /active-users` & `POST /run-callback`: Header `X-LTL-API-Key` (Vergleich gegen Option) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_active_users()`, `run_callback()`)
- Limits/Usage: `posts_this_month` + `posts_period_start` in `wp_ltl_saas_settings`; Monat-Rollover Reset in `/make/tenants`; Inkrement bei `run_callback(status=success)` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`, `run_callback()`)
- Plan-Limits aktuell als Code-Map (free/starter/pro/agency) umgesetzt und als Derived-Limit verwendet — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`ltl_saas_plan_posts_limit`, `ltl_saas_get_tenant_state`)

## 2) Open Issues Status (Tabelle: Issue | Status | Evidence | Test/Gaps)

| Issue | Status | Evidence | Test/Gaps |
|---|---|---|---|
| #20 — M5: Onboarding Wizard (Connect WP → RSS → Start) | PARTIAL | Setup-Progress UI: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (Block „Dein Setup-Fortschritt“). Test-Endpunkte: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`/test-connection`, `/test-rss`). Onboarding-Doc: `docs/product/onboarding-detailed.md` | **Gaps:** (1) Kein expliziter Link im UI zu `docs/product/onboarding.md`/`onboarding-detailed.md`. (2) Wizard deckt nur Step 1/2 ab; Plan-Status + „letzter Run“ als Setup-Schritt nicht als Wizard-Status geführt. (3) Kein „Test Run starten“ Button/Action im Portal (Doc erwähnt). |
| #17 — M4: Basic Retry Strategie (429/5xx) | PARTIAL | Retry-Konzept dokumentiert: `docs/engineering/make/retry-strategy.md`. Smoke Tests: `docs/testing/smoke/issue-17.md`. Callback speichert `raw_payload` und kann retry-Metadaten transportieren (ohne Spalten) — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`run_callback()` erzeugt `raw_payload`) | **Gaps:** (1) Keine Make-Multi-Tenant Blueprint-Datei im Repo, die die Retry-Handler tatsächlich implementiert (nur Docs). (2) DB-Spalten `attempts/last_http_status/retry_backoff_ms` existieren nicht in `wp_ltl_saas_runs` (nur Doc-Vorschlag). (3) `status` Werte sind inkonsistent über Docs (`success/failed` vs `success/error`). |
| #8 — M1: Plans + Limits Datenmodell (Basic/Pro/Studio) | PARTIAL | Settings Tabelle enthält `plan`, `is_active`, `posts_this_month`, `posts_period_start` — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`CREATE TABLE ... ltl_saas_settings`). Limit-Berechnung per Plan-Map — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` (`ltl_saas_plan_posts_limit`, `ltl_saas_get_tenant_state`). Usage Enforcement + Remaining im Tenant Pull — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()` skip/remaining). | **Gaps:** (1) Issue fordert explizite Felder `posts_limit_month`, `posts_used_month` (persistiert) – aktuell derived (`posts_limit_month`) und `posts_this_month` (anderer Name). (2) Plan-Namen im Issue/Docs (`Basic/Pro/Studio`) weichen von Code (`free/starter/pro/agency`) ab; `docs/product/pricing-plans.md` weicht ebenfalls ab. (3) „User hat Plan + Limit sichtbar“: im Customer Dashboard wird Plan/Limit aktuell nicht als klare UI angezeigt (nur Lock-Screen bei `is_active=0` + Runs-Tabelle). |
| #7 — M1: Gumroad Webhook Endpoint im WP-Plugin | PARTIAL | Implementiert ist `POST /wp-json/ltl-saas/v1/gumroad/ping` mit Secret-Check + User-Provisioning + Plan-Set + Refund->Deactivate — Evidence: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`register_routes()` + `gumroad_ping()`). Secrets/Mapping in Options: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php` und Admin UI: `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`. Smoke Tests Sprint 07: `docs/testing/smoke/sprint-07.md`. | **Gaps:** (1) Route-Name mismatch: Issue fordert `/gumroad/webhook`, Code+Docs nutzen `/gumroad/ping`. (2) Event-Typen (sale/subscribe/cancel/refund) werden nicht explizit ausgewertet; es gibt nur `refunded` Flag. (3) „Log in WP (minimal)“ ist nur rudimentär über `error_log` vorhanden. |

## 3) Risk List (P0/P1/P2, jeweils konkrete Fix-Idee + Pfade)

### P0 (Launch-Blocker / Security / Revenue)

- P0: Billing Route / Semantik mismatch zu Issue #7 (Webhook vs Ping) → Fix: Route vereinheitlichen (alias oder Umbenennung) + Event-Semantik in Code/Docs synchronisieren — Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `docs/billing/gumroad.md`, `docs/testing/smoke/sprint-07.md`
- P0: Welcome Email enthält Klartext-Passwort (Account Provisioning) → Fix: statt Passwort senden: `wp_set_password` vermeiden / ausschließlich Password-Reset-Link, oder Invite Flow; Audit Trail — Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`send_gumroad_welcome_email()`)
- P0: Secrets in `wp_options` unverschlüsselt (API key, Make token, Gumroad secret) → Fix: Encrypt-at-rest für Options (z.B. via `LTL_SAAS_Portal_Crypto` oder WP Secrets API/Env); zusätzlich Hardening + minimaler Scope — Pfade: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php`, `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
- P0: `/make/tenants` liefert decrypted App Password (hoch-sensitiv); Abuse surface bei Token-Leak → Fix: Token Rotation Policy, allowlist IP/Basic WAF, optional per-tenant key, Logging/Rate limit; ggf. alternative: Make zieht App Password nur on-demand — Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`get_make_tenants()`, `permission_make_tenants()`), `docs/engineering/make/multi-tenant.md`

### P1 (Reliability / Data Correctness)

- P1: Callback ist nicht idempotent; doppelte Callbacks können `posts_this_month` mehrfach erhöhen → Fix: Idempotency-Key (Make execution id) persistieren und unique enforce; Update statt Insert bei Duplikat — Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php` (`run_callback()`), DB Schema in `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- P1: Month rollover reset findet sowohl in `/make/tenants` als auch `run_callback` statt; mögliche Race Conditions bei parallel laufenden Scenarios → Fix: atomarer Update/Locking Strategy (z.B. `UPDATE ... WHERE posts_period_start != current_month`) — Pfade: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- P1: Docs/Contracts inkonsistent (Headers, Paths, Status values) → Fix: API Contract „single source of truth“ + Smoke Tests alignen — Pfade: `docs/reference/api.md`, `docs/testing/smoke/sprint-04.md`, `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`

### P2 (DX / Ops / Repo Hygiene)

- P2: `docs/testing/smoke/sprint-04.md` enthält doppelte Blöcke + falsche Header/Auth Beispiele (Bearer vs `X-LTL-SAAS-TOKEN`) → Fix: Doc cleanup (prefer MOVE nach `docs/archive/` statt delete) — Pfade: `docs/testing/smoke/sprint-04.md`, `docs/archive/`
- P2: Repo enthält Make Blueprints, aber Multi-Tenant Loop Blueprint ist nicht sichtbar/ableitbar (nur generische Bot Blueprints) → Fix: Multi-Tenant Blueprint export + sanitize pipeline nutzen — Pfade: `blueprints/sanitized/**`, `scripts/sanitize_make_blueprints.py`, `docs/engineering/make/multi-tenant.md`
- P2: Release Packaging ist vorhanden, aber Changelog fehlt als Artefakt (Checklist referenziert) → Fix: schlanker `CHANGELOG.md` oder Release Notes Prozess (falls gewünscht) — Pfade: `scripts/build-zip.ps1`, `docs/releases/release-checklist.md`

## 4) Master Plan (Phasen + Tasks)

### Phase 0 — Launch Blockers (Billing + Plans + UX Contract)

Task: Billing Endpoint finalisieren (Issue #7)
- Goal: Gumroad Events zuverlässig freischalten/sperren; Route & Docs konsistent.
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `docs/billing/gumroad.md`, `docs/reference/api.md`, `docs/testing/smoke/sprint-07.md`
- DoD: (1) Endpoint-Contract matcht Issue: `/gumroad/webhook` (oder documented alias) + Shared Secret Validation. (2) Event-Semantik (sale/subscribe/cancel/refund) abgedeckt. (3) Smoke Test Sprint 07 läuft end-to-end.
- Impact: High
- Komplexität: M

Task: Plans/Limits Datenmodell vereinheitlichen (Issue #8)
- Goal: Plan + Limit sind konsistent benannt, abgeleitet/persistiert, und im Portal sichtbar.
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`, `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `docs/product/pricing-plans.md`, `docs/reference/api.md`
- DoD: (1) Plan-Namen in Code+Docs konsistent (z.B. Basic/Pro/Studio oder Starter/Pro/Agency). (2) Usage Felder klar: `posts_used_month` & `posts_limit_month` (persistiert oder sauber derived + dokumentiert). (3) Dashboard zeigt Plan + Usage auch ohne Runs.
- Impact: High
- Komplexität: M

Task: Onboarding Wizard finalisieren (Issue #20)
- Goal: Neukunde kommt ohne Support ans Ziel (Connect WP → RSS → First Run + Plan Status).
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`, `docs/product/onboarding.md`, `docs/product/onboarding-detailed.md`
- DoD: (1) UI enthält klare Schritt-für-Schritt Hinweise + Link zu Onboarding Doc. (2) Setup Progress umfasst mindestens: Connection OK, RSS OK, Plan aktiv, letzter Run. (3) Smoke-Test-Anleitung im Onboarding ist mit UI deckungsgleich.
- Impact: High
- Komplexität: M

Task: API Contract & Smoke Tests konsolidieren
- Goal: Keine widersprüchlichen Header/Paths/Status über Docs.
- Files to touch: `docs/reference/api.md`, `docs/reference/architecture.md`, `docs/testing/smoke/sprint-04.md`, `docs/engineering/make/multi-tenant.md`
- DoD: (1) Alle Beispiele nutzen echte Header (`X-LTL-SAAS-TOKEN`, `X-LTL-API-Key`). (2) Pfade stimmen mit `register_routes()` überein. (3) Statuswerte (`success`/`error`) sind konsistent.
- Impact: High
- Komplexität: S

### Phase 1 — Reliability & Abuse Hardening

Task: Callback Idempotency
- Goal: Doppelte Callbacks dürfen Usage nicht doppelt zählen.
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`
- DoD: (1) Callback akzeptiert `run_id`/`execution_id` und enforced uniqueness. (2) Usage increment ist idempotent. (3) Regression: Unknown tenant wird weiter abgewiesen.
- Impact: High
- Komplexität: M

Task: Retry/Backoff Telemetrie (Issue #17)
- Goal: Nach Retry sauber loggen (mindestens in `raw_payload`, optional Spalten).
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php`, `docs/engineering/make/retry-strategy.md`, `docs/testing/smoke/issue-17.md`
- DoD: (1) Make sendet `attempts/last_http_status/retry_backoff_ms` im Callback. (2) Portal speichert diese zuverlässig (Spalten oder JSON). (3) Smoke Tests Issue 17 sind ausführbar.
- Impact: Med
- Komplexität: M

Task: Month rollover atomic machen
- Goal: Kein Race bei parallelen Runs/Tenant Pulls.
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- DoD: Reset+Increment erfolgen atomar und reproduzierbar; dokumentierte Testcases `docs/testing/smoke/sprint-04.md` passen.
- Impact: Med
- Komplexität: S/M

Task: Rate limiting / brute-force surface reduzieren
- Goal: Schutz gegen API-Key/Token brute-force und DoS.
- Files to touch: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`, `docs/ops/proxy-ssl.md` (falls WAF/Proxy genutzt)
- DoD: Baseline-Schutz (z.B. IP allowlist am Reverse Proxy, oder WP transient based throttling) + Logging.
- Impact: Med
- Komplexität: M

### Phase 2 — Production Readiness (Packaging, Docs, Repo Hygiene)

Task: Release Pipeline verifizieren
- Goal: Reproduzierbares Plugin ZIP + Hashes, klare Release Steps.
- Files to touch: `scripts/build-zip.ps1`, `docs/releases/release-checklist.md`, `docs/testing/logs/testing-log.md`
- DoD: `build-zip.ps1` erzeugt ZIP + SHA256; Smoke Tests protokolliert; Checklist ist vollständig.
- Impact: Med
- Komplexität: S

Task: Docs Cleanup (MOVE statt Delete)
- Goal: Doppelte/obsolete Docs in `docs/archive/` verschieben, aktive Docs konsistent.
- Files to touch: `docs/testing/smoke/sprint-04.md`, `docs/archive/**`, `docs/README.md`
- DoD: Keine doppelten Sektionen; Examples stimmen; Archiv enthält alte Varianten; `docs/README.md` bleibt Einstiegspunkt.
- Impact: Low/Med
- Komplexität: S

Task: Multi-Tenant Blueprint als Deliverable
- Goal: Kunden-/Team-Deliverable: tatsächliches Multi-Tenant Make Scenario als sanitized blueprint.
- Files to touch: `blueprints/**`, `scripts/sanitize_make_blueprints.py`, `docs/engineering/make/multi-tenant.md`
- DoD: Blueprint enthält Module für `/make/tenants` Pull + Iterator + WP Post + `/run-callback` inkl. Retry Handler; Sanitizer entfernt Secrets.
- Impact: Med
- Komplexität: L
