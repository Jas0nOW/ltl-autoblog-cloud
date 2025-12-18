# Roadmap — LTL AutoBlog Cloud (v0.1)

> Ziel: Login‑Portal + Abo → Nutzer verbindet eigene WP‑Site (oder später Hosting‑Bonus) → Auto‑Blog läuft.

## M0 — Projekt-Setup
- Portal‑WP auf `app.lazytechlab.de`
- Repo-Struktur + Docs

## M1 — Abo & Freischaltung
- Gumroad/PayPal Produkt(e) anlegen
- Webhook → User freischalten/sperren

## M2 — Dashboard & Settings
- RSS, Sprache, Ton, Frequenz, Draft/Publish
- „Connect WordPress“ via Application Password

## M3 — Make Engine (Multi‑Tenant)
- Portal liefert aktive Nutzer/Settings via REST
- Make iteriert Nutzer → RSS → AI → WP REST Post

## M4 — Logs, Limits, Stabilität
- Runs speichern + im Dashboard anzeigen
- Plan‑Limits (Posts/Monat), Basic Retries

## M5 — Launch Ready
- Landing, Pricing, Demo‑Blogs, Onboarding Wizard
