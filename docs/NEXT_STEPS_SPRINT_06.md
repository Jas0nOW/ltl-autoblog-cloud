# Sprint 06 — Closeout + Launch-Ready (nach Audit V3)

**Datum:** 2025-12-18

Dein Audit V3 sagt: **#9–#16 sind ✅**. Offene Punkte sind jetzt nur noch „Profi-Finish“:
- Smoke Tests für Limits (#16) wirklich ausführen & loggen
- Admin UI: API-Key (maskiert + regenerieren)
- Proxy/SSL Setup Hinweis (damit `is_ssl()` nicht falsche 403 wirft)
- GitHub Hygiene: Issues schließen, Release/ZIP bauen

---

## Modell-Empfehlung (deine verfügbaren Modelle)
- Code/Implementierung: **GPT‑4.1 (0x)**
- Docs/Copy: **GPT‑4o (0x)**
- Commit/PR Text: **GPT‑5 mini (0x)**
- Nur wenn Kontext „zu groß“ wird: **Gemini 2.5 Pro (BYOK)**

---

## Reihenfolge (Abarbeiten)

1) **A — Smoke Tests #16 + Testing Log**
2) **B — Admin UI: API-Key maskiert + Regenerate**
3) **C — Proxy/SSL: Doc + optional wp-config Snippet**
4) **D — GitHub Closeout: Issues schließen + Labels/Milestones sauber**
5) **E — Release Pack: ZIP bauen + Changelog**

---

## Definition of Done
- `docs/TESTING_LOG.md` ist ausgefüllt (Limits-End-to-End)
- API-Key ist im Admin UI konfigurierbar (maskiert, regenerierbar)
- Docs enthalten Proxy/SSL Hinweis (X-Forwarded-Proto)
- Issues #9–#16 sind geschlossen (auto oder manuell)
- Release ZIP liegt in `dist/` (oder GitHub Release Draft ist vorbereitet)
