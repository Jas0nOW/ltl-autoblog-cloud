# COPILOT_PROMPTS_SPRINT_02.md — M2/M3 → Richtung MVP (Portal + Make Multi‑Tenant)

> Working branch: `Phase1-Core`
> Ziel dieses Sprints: **Multi‑Tenant MVP** stabil bekommen (Portal liefert Config → Make iteriert → Portal bekommt Run‑Callback)

## Welche Modelle nutzen?

- **Prompt A (Repo‑Audit, viele Dateien):** Gemini **2.5 Pro** (BYOK) *oder* GPT‑4.1 (0x) wenn’s reicht.
- **Prompt B–E (Implementierung):** GPT‑4.1 (0x)
- **Text/Copy/Docs:** GPT‑4o (0x)
- **Commit/PR‑Texte:** GPT‑5 mini (0x)

---

## Sprint Scope (Issues)

**M2 (Portal):**
- #9 Settings‑UI im Portal
- #10 Connect WordPress (verschlüsselt speichern)
- #11 Access Control (Dashboard nur für aktive Abos)

**M3 (Make Engine):**
- #12 `active-users` Endpoint
- #14 Run Callback Endpoint
- #13 Make Szenario refactor → Multi‑Tenant Loop

**Optional (wenn Luft):**
- #15 Runs Tabelle + Dashboard Ansicht

---

# PROMPT A — Repo Status Audit (damit wir wissen, was wirklich fertig ist)

> **Copilot Chat Prompt (einfügen):**

Du bist mein Lead-Engineer. Analysiere das gesamte Repo im Workspace (Branch `Phase1-Core`) und gib mir:

1) Eine kurze Liste der wichtigsten Features, die bereits implementiert sind (Portal UI, REST, DB Tabellen).
2) Mapping: Welche der Issues **#9, #10, #11, #12, #13, #14, #15** sind:
   - ✅ vollständig (inkl. minimaler Tests/Docs)
   - 🟡 teilweise (was fehlt genau)
   - ❌ noch nicht angefangen
3) Für jedes Issue: die zentralen Dateien/Ordner, die betroffen sind.
4) Eine „Top 5 Risiken“-Liste (Security, Data handling, Auth, missing validation).
5) Konkrete Next Steps: 3 kleine Schritte (max 60–90 min pro Schritt).

**Wichtig:** Nenne konkrete Pfade/Funktionsnamen, nicht nur „mach mal“.

---

# PROMPT B — Make Pull Endpoint (Multi‑Tenant Config Pull)

> Ziel: Make soll sich beim Portal eine Liste aktiver Tenants ziehen können.

**Copilot Chat Prompt:**

Implementiere einen neuen REST Endpoint:

- `GET /wp-json/ltl-saas/v1/make/tenants`
- Zugriff **nur** mit Header: `X-LTL-SAAS-TOKEN: <token>`
- Token kommt aus WP Option `ltl_saas_make_token` (Admin kann ihn in Settings setzen; falls leer, Endpoint disabled → 403)
- Response: JSON array von Tenants, minimal:
  - `tenant_id`
  - `site_url`
  - `wp_username`
  - `wp_app_password` (decrypted, nur für Make)
  - `rss_url`
  - `language`
  - `tone`
  - `publish_mode` (`draft|publish`)
  - `frequency` (wenn vorhanden)
  - `plan`
  - `is_active`

**Security:**
- strict auth check, 403 ohne token, 401 wenn header fehlt
- sanitize alle Ausgaben (URLs validieren)
- niemals secrets in logs

**Akzeptanz:**
- Curl Beispiel in `docs/reference/api.md` ergänzen
- Endpoint liefert 200 + JSON wenn Token stimmt
- Endpoint liefert 403 wenn Token fehlt/falsch/leer

---

# PROMPT C — Make Callback: Run‑Events robust speichern + anzeigen

> Ziel: #14 wirklich „felt real“ machen, inkl. UI.

**Copilot Chat Prompt:**

Erweitere den Run‑Callback Flow so, dass ein Run **persistiert** wird:

- Lege DB Tabelle an (falls noch nicht existiert) `wp_ltl_saas_runs`
  - `id`, `tenant_id`, `status`, `started_at`, `finished_at`, `posts_created`, `error_message`, `raw_payload` (gekürzt), `created_at`
- Der Callback Endpoint akzeptiert `POST` JSON:
  - `tenant_id`, `status`, `posts_created`, `error_message`, `meta` (optional)
- Speichere einen Run Eintrag (raw_payload max 4–8 KB)
- In Dashboard UI: zeige „Letzter Run“ + Button „Runs anzeigen“ (letzte 10)

**Akzeptanz:**
- Keine Fatals bei leerem Payload
- Dashboard zeigt Last Run State
- Tabelle wird nur einmal erstellt (dbDelta)

---

# PROMPT D — Access Control (#11) minimal umsetzbar (MVP)

> Ziel: Kein echtes Billing nötig – aber „aktiv / inaktiv“ muss wirken.

**Copilot Chat Prompt:**

Baue eine MVP-Access-Control:

- Ein User ist „aktiv“, wenn in Tenant record `is_active=1` ODER Option `ltl_saas_force_active` gesetzt ist (für Testing).
- Wenn nicht aktiv:
  - Dashboard zeigt eine **lock screen** Box: „Abo erforderlich“ + Link (Platzhalter) zur Pricing Seite.
  - REST Endpoints (Make Pull, Settings Save) geben 403.
- Wenn aktiv: normales Verhalten.

**Akzeptanz:**
- Nicht‑aktive User können Settings nicht speichern
- Make Endpoints sind geschützt
- UI ist freundlich und professionell (kein „Error dump“)

---

# PROMPT E — Make Multi‑Tenant Loop (#13) — Schritt‑für‑Schritt Anleitung (Docs)

> Das ist kein Code, sondern Make‑Umbau. Copilot soll dir dafür eine saubere Anleitung in `docs/engineering/make/multi-tenant.md` schreiben.

**Copilot Chat Prompt:**

Erstelle eine Anleitung `docs/engineering/make/multi-tenant.md`:

- Überblick: Trigger (Scheduler) → HTTP Pull Tenants → Iterator → pro Tenant: RSS → AI → WP Create Post → Callback
- Welche Make Module (generisch) nötig sind
- Beispiel Payloads:
  - vom Portal an Make (`/make/tenants`)
  - von Make zurück ans Portal (Run Callback)
- Fehlerhandling minimal: bei Fehler pro Tenant Callback mit `status=failed`
- Sicherheitsnotiz: Token geheim halten, HTTPS erzwingen

**Akzeptanz:**
- Eine Person kann nach der Anleitung das Scenario umbauen, ohne dich zu fragen.

---

# PROMPT F — Abschluss: Smoke Test + Commit

1) Lokal Smoke Test nach Playbook Abschnitt 7
2) Dann `COPILOT_PROMPT_COMMIT_AND_PR.md` nutzen für sauberen Commit (Scope: issue)
3) Push

**Hinweis:** Issues bleiben offen bis Merge in `main`. Kommentiere aber in jedem Issue: „Done on Phase1-Core (commit <hash>)“.

