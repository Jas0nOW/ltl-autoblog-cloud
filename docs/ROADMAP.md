# LTL AutoBlog Cloud — Roadmap (v0.1)

> Ziel: Ein Login‑Portal, in dem Nutzer ein Abo abschließen, ihren eigenen WordPress‑Blog verbinden (oder optional von dir gehostet bekommen) und anschließend automatisiert Inhalte aus einem frei wählbaren RSS‑Feed in ihrem gewünschten Stil/Sprache veröffentlichen lassen.  
> MVP = **Blog‑Bot** als SaaS. Add‑ons später: Cross‑Promoter, Newsletter, Video‑Bot, Watchdog.

---

## 0) Produkt in einem Satz
**„Abo abschließen → RSS + Stil einstellen → WordPress verbinden → Auto‑Blog läuft.“**

---

## 1) Scope (was ist V1, was nicht?)

### V1 (MVP, vermietbar)
- Login/Account auf deiner WP‑Plattform (Portal)
- Abo‑Status via Gumroad/PayPal → Freischaltung
- Dashboard: Nutzer kann konfigurieren
  - Nische/Thema (Tech, Kunst, …)
  - RSS‑Feed(s)
  - Sprache + Ton (locker/seriös/etc.)
  - Posting‑Modus (Draft/Publish)
  - Frequenz (z. B. 1/Tag, 3/Woche)
  - Optional: SEO‑Aggressivität
- WordPress‑Ziel verbinden (User‑eigene WP‑Seite) via **Application Password**
- Make‑Engine läuft **multi‑tenant**: verarbeitet alle aktiven Nutzer nach Plan
- Run‑Historie + Fehlerlog (basic)
- Usage‑Limits pro Plan (z. B. Posts/Monat)

### Nicht in V1 (später)
- Vollautomatische „Blog‑Provisionierung“ auf deinem Hosting (Bonus‑Feature später)
- Stripe‑Billing (Gumroad/PayPal reicht am Anfang)
- Komplexe Team‑Accounts / Rollen / White‑Label
- Vollständige In‑App Content‑Editoren (wir bleiben bei Auto‑Runs + Logs)

---

## 2) Bausteine (Architektur)

### A) WordPress Portal (Hostinger)
- Subdomain z. B. `app.lazytechlab.de`
- Theme/Design: SaaS‑Landing + simples Dashboard
- **Eigenes Plugin**: `ltl-saas-core`
  - Speichert User‑Settings
  - Speichert „Connected WordPress“ (URL, Username, App‑Password verschlüsselt)
  - Stellt REST‑API für Make bereit (aktiv, Settings, Limits, Run‑Callback)
  - Verarbeitet Gumroad/PayPal Webhooks → Abo‑Status

### B) Make.com Engine (multi-tenant)
- 1 Szenario „AutoBlog Engine“
- Ablauf:
  1. Holt Liste aktiver Nutzer + Settings aus Portal‑REST‑API
  2. Pro Nutzer: RSS lesen → AI JSON → WP Post via REST → Run loggen
  3. Callback ans Portal: success/failed + Output + costs/usage

---

## 3) Deine 6 Bots (wie wir sie einordnen)

### Core‑Produkt (MVP)
- **Blog‑Bot v2.1** → wird „AutoBlog Engine“

### Add‑ons (nach Launch)
- **Cross‑Promoter** → Social Push / Traffic‑Add‑on
- **Newsletter‑Bot** → wöchentliches Digest‑Add‑on
- **Video‑Bot** → Pro/Studio‑Tier Add‑on
- **Admin‑Watchdog** → eher interner Ops‑Bot (für dich / Support)
- **Sales‑Bot** → interner Ops‑Bot (für dich / Verkaufstracking)

---

## 4) Meilensteine (Roadmap)

### M0 — Projekt‑Setup (0.5–1 Tag)
- [ ] Repo/Ordnerstruktur anlegen (`/docs`, `/wp-plugin`, `/make`)
- [ ] Naming fixieren: „LTL AutoBlog Cloud“
- [ ] Subdomain `app.` aufsetzen + WordPress installieren

**Done wenn:** Portal läuft (leere Seite), Repo hat diese Doku.

---

### M1 — Abo & Freischaltung (1–2 Tage)
- [ ] Gumroad Produkt(e) anlegen (Basic/Pro/Studio)
- [ ] In WordPress: Abo‑Status speichern pro User (z. B. `ltl_plan`)
- [ ] Webhook Endpoint im Plugin: `/wp-json/ltl-saas/v1/gumroad/webhook`
- [ ] Rule: Nur aktive Abos dürfen Dashboard/Run nutzen

**Done wenn:** Test‑Kauf → User wird freigeschaltet; Kündigung → gesperrt.

---

### M2 — Dashboard & Settings (1–3 Tage)
- [ ] Dashboard Seite (Shortcode) im Plugin: Settings Formular
- [ ] Settings speichern (DB)
- [ ] Validierung/Sanitization
- [ ] „Connect WordPress“: URL + Username + App‑Password speichern (verschlüsselt)

**Done wenn:** User kann Settings speichern und sieht sie wieder.

---

### M3 — Make Engine (multi-tenant) (2–5 Tage)
- [ ] REST Endpoint: `GET /active-users` liefert: user_id, plan, settings, wp_credentials_ref
- [ ] Make Szenario:
  - [ ] holt active users
  - [ ] iteriert pro user
  - [ ] RSS fetch pro user
  - [ ] Gemini prompt als Template (niche/language/tone)
  - [ ] Post via WP REST (http request) an User‑WP
  - [ ] Callback ans Portal mit status + link
- [ ] „Dry-run“ Mode zum testen (nur draft)

**Done wenn:** 1 echter Test‑User bekommt automatisch einen Draft‑Post in seiner WP‑Site.

---

### M4 — Logs, Limits, Stabilität (1–3 Tage)
- [ ] Tabelle/Storage: `runs` (user_id, started, finished, status, message, post_url)
- [ ] Usage: posts/month zählen
- [ ] Limits pro Plan enforce (in Make oder Portal‑API)
- [ ] Basic Retry Strategie (z. B. 1 Retry bei HTTP 429/5xx)

**Done wenn:** Dashboard zeigt letzte Runs + „X/Y Posts diesen Monat“.

---

### M5 — Launch‑Ready (1–2 Tage)
- [ ] Landingpage: Was ist’s? Preise. Demo‑Beispiele. FAQ.
- [ ] Onboarding‑Wizard: „1) Plan 2) Connect WP 3) RSS 4) Start“
- [ ] Support: Kontakt + einfache Fehlerhilfe (z. B. „WP Auth failed“)

**Done wenn:** Fremder User kann kaufen → verbinden → läuft.

---

### M6 — Add‑ons (nach Umsatz)
- [ ] Cross‑Promoter als Add‑on (Pro)
- [ ] Newsletter‑Bot (Pro)
- [ ] Video‑Bot (Studio)
- [ ] Optional: Hosting‑Bonus (Provisioning)

---

## 5) Datenmodell (minimal)
- `user_meta` (Plan, Status, Limits)
- `ltl_settings` (user_id, niche, language, tone, rss_feeds, frequency, mode, …)
- `ltl_connections` (user_id, wp_url, wp_user, wp_app_password_encrypted)
- `ltl_runs` (user_id, status, started_at, finished_at, post_url, error)

---

## 6) REST API (Portal → Make)
- `GET /wp-json/ltl-saas/v1/active-users`
- `POST /wp-json/ltl-saas/v1/run-callback` (Make → Portal)
- `POST /wp-json/ltl-saas/v1/gumroad/webhook`
- (Optional) `POST /wp-json/ltl-saas/v1/test-connection`

---

## 7) Definition of Done (für V1)
- User kann Abo abschließen
- User kann WordPress verbinden
- User kann RSS + Stil konfigurieren
- System produziert automatisch Posts (Draft oder Publish)
- User sieht Logs + Limits
- Basic Fehlerfälle sind erklärbar (Auth, RSS kaputt, Rate Limit)

---

## 8) Arbeitsmodus mit Copilot/Cursor (kurz)
- Cursor: Architektur/Refactor, Multi‑File Sweeps
- Copilot: Feature‑Slices (Webhook, REST Endpoints, Dashboard, Sanitization)
- Regel: Jede große Änderung = kleiner Commit + kurzer Test

---

## 9) Nächster Schritt (Startpunkt)
**M0 + M1**: Portal aufsetzen + Gumroad Freischaltung bauen.
Danach M2 (Settings‑Dashboard), dann M3 (Make Engine).

