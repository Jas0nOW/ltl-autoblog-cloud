# Sprint — Issue #20: Onboarding Wizard (Connect WP → RSS → Start)

**Issue Link:** https://github.com/Jas0nOW/ltl-autoblog-cloud/issues/20

## Ziel (DoD)
- Neukunde kommt **ohne Support** durch Setup (Issue-Definition).
- Dashboard zeigt **Schritt-für-Schritt** Fortschritt + klare “Next action” Buttons.
- `docs/ONBOARDING.md` existiert und ist aus UI verlinkt.

## Empfohlenes Modell
- **UI/Logic:** GPT‑4.1 (0x)
- **Texte/Hints:** GPT‑4o (0x)

---

## Prompt A — `docs/ONBOARDING.md` (Super klar)
**Modell:** GPT‑4o (0x)

Erstelle/aktualisiere `docs/ONBOARDING.md` mit:
1) Account erstellen & einloggen
2) WordPress verbinden (URL + Username + App Password)
3) RSS Feed setzen + Sprache + Ton
4) Draft/Publish wählen + Frequenz
5) “Test Run” (wie erkenne ich, dass es läuft?)
6) Troubleshooting (Top 10 Fehler)

Kurz, direkt, ohne Blabla.

---

## Prompt B — Dashboard: Setup-Progress Block
**Modell:** GPT‑4.1 (0x)

Im Dashboard Shortcode (`[ltl_saas_dashboard]`) ergänzen:
- Progress UI (Cards/Steps mit ✅/⚠️)
- Steps:
  1) Connect WordPress (configured?)
  2) RSS + Language/Tone gesetzt?
  3) Plan aktiv? (is_active)
  4) First Run erfolgreich? (letzter run status)
- Jede Card hat Button “Öffnen” der direkt zur passenden Sektion springt (Anchor links oder separate page/tab).

Akzeptanz:
- Wenn etwas fehlt, sieht der User *sofort* was und wo er klicken muss.

---

## Prompt C — Hint Texts + Inline Validation
**Modell:** GPT‑4o (0x)

Ergänze in den Formularen:
- Kurze Hilfe-Texte (1 Satz) + Beispiele
- Inline Validierung (z.B. RSS URL muss URL sein, WP URL https)
- “Was passiert als nächstes?” Text nach Save

---

## Prompt D — Optional: “Test Connection” & “Test RSS” Buttons
**Modell:** GPT‑4.1 (0x)

Wenn noch nicht vorhanden:
- Button: “Test WordPress Connection” → zeigt Ergebnis (ok/fail) + Fehlermeldung freundlich
- Button: “Test RSS” → holt Titel des neuesten Items (nur 1 request), zeigt Preview

Wichtig:
- Timeouts klein halten
- Keine Secrets ausgeben

---

## Prompt E — Smoke Tests + PR
**Modell:** GPT‑5 mini (0x)

Erstelle `docs/SMOKE_TEST_ISSUE_20.md`:
- Fresh user ohne Settings → sieht Step 1–4 offen
- Nach Connect WP → Step 1 ✅
- Nach RSS Setup → Step 2 ✅
- Wenn is_active=0 → Step 3 zeigt “Upgrade” CTA
- Nach erfolgreichem Run → Step 4 ✅

PR Text enthält: `Closes #20`
