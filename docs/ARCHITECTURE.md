# Architektur — LTL AutoBlog Cloud

## Überblick
**WordPress Portal** ist das SaaS‑Frontend (Login, Abo, Settings, Logs).  
**Make.com** ist die Engine (Multi‑Tenant Verarbeitung).

```
[Kunde] -> (WordPress Portal: Login + Settings + Abo)
   |                  |
   | (REST: active users + configs)
   v                  |
[Make Engine] --------+
   |
   | (HTTP) WordPress REST API (je Kunde)
   v
[Kunden-WordPress]  -> Posts (Draft/Publish)
```

## Komponenten

### 1) WordPress Portal (Hostinger)
- Subdomain: `app.lazytechlab.de`
- Plugin: `ltl-saas-core`
  - Speichert Settings pro User
  - Speichert WP‑Connection pro User (URL + Username + App‑Password **verschlüsselt**)
  - Abo‑Status (Gumroad/PayPal)
  - REST Endpoints für Make
  - Run‑Logs + Usage

### 2) Make Engine (Multi‑Tenant)
Ein Szenario „AutoBlog Engine“:
1. `GET /active-users` → Liste aktiver Kunden + Settings
2. pro Kunde:
   - RSS holen/parsen
   - Prompt Template (niche/language/tone)
   - WP Post via REST (Basic Auth mit Application Password)
3. `POST /run-callback` → Status + Post‑URL + Fehler

## Minimal-API (Portal)
- `GET  /wp-json/ltl-saas/v1/active-users`
- `POST /wp-json/ltl-saas/v1/run-callback`
- `POST /wp-json/ltl-saas/v1/gumroad/webhook`
- optional: `POST /wp-json/ltl-saas/v1/test-connection`

## Datenmodell (minimal)
- `settings` (user_id, rss_feeds, niche, language, tone, frequency, mode, …)
- `connections` (user_id, wp_url, wp_user, wp_app_password_encrypted)
- `runs` (user_id, status, started_at, finished_at, post_url, error, meta)

## Warum Multi‑Tenant?
- 1 Engine → viele Kunden (billiger, einfacher zu pflegen)
- Limits/Pläne zentral kontrollierbar
