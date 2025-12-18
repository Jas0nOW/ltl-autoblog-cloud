# Sprint 07 — Billing & Onboarding (Gumroad Ping → Tenant aktivieren)

**Datum:** 2025-12-18

Du hast jetzt ein technisch solides Portal + Make Multi‑Tenant Pull (inkl. Limits).  
Der nächste echte “Money-Move” ist: **Kauf → Account/Plan aktiv → Kunde kann sofort starten.**

---

## Ziel (Definition of Done)
- Gumroad “Ping endpoint” ruft dein Portal auf und **aktiviert/aktualisiert** den Tenant anhand von `product_id` + `email`.
- Bei `refunded=true` wird der Tenant **deaktiviert** (is_active=0).
- Admin UI: du kannst **Gumroad Secret** + **Product-ID→Plan Mapping** verwalten (ohne DB-Fummelei).
- Docs: Setup-Schritte + Test-Ping.

---

## Modell-Empfehlung (deine Copilot-Modelle)
- Implementierung (REST + Admin + DB): **GPT‑4.1 (0x)**
- Docs/Copy: **GPT‑4o (0x)**
- Commit/PR Texte: **GPT‑5 mini (0x)**
- Wenn Copilot Kontext verliert: **Gemini 2.5 Pro (BYOK)**

---

## Branch
- `feat/billing-gumroad-ping`

---

## Reihenfolge (Abarbeiten)
1) Prompt A — Settings/Admin UI (Secret + Product Mapping)
2) Prompt B — REST: `/gumroad/ping` Endpoint (x-www-form-urlencoded)
3) Prompt C — Provisioning: User finden/erstellen + Settings row upsert
4) Prompt D — Refund/Cancel Handling (refunded=true → deactivate)
5) Prompt E — Docs: Setup & Test Ping
6) Prompt F — Smoke Test + Commit + PR (`Closes #17`)

---

## Sicherheit (MVP)
Gumroad Ping hat **kein** Signatur-Header. Daher sichern wir das Endpoint pragmatisch ab:
- `?secret=<random>` in der Ping-URL (in Admin konfigurierbar)
- HTTPS only (Gumroad empfiehlt HTTPS)
