# COPILOT_PROMPTS_SPRINT_04_LIMITS_AND_HARDENING.md

> Sprint 04 = Issue #16 (Posts/Monat Limits enforce) + kleine Hardening-Schritte.

---

## Prompt A — Zentraler Tenant State Helper (reduziert Redundanz)
**Empfohlenes Modell:** GPT‑4.1 (0x)

Baue eine zentrale Helper-Funktion (Ort: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php` oder passendes Core-File):

`ltl_saas_get_tenant_state( int $user_id ): array`

Sie soll aus DB lesen und zurückgeben:
- `user_id`
- `plan` (string, default "free" wenn leer)
- `is_active` (bool)
- `posts_this_month` (int, default 0)
- `posts_limit_month` (int, abhängig vom plan, siehe Mapping unten)
- `posts_period_start` (YYYY-MM-01 als string oder DATE)

Plan→Limit Mapping (MVP, hardcoded):
- free: 20
- starter: 80
- pro: 250
- agency: 1000

Lege zusätzlich eine Helper `ltl_saas_plan_posts_limit( string $plan ): int` an.
Nutze diese Helper danach überall, wo du bisher mehrfach DB abfragst.

Am Ende: Liste der geänderten Dateien + kurzer Testplan.

---

## Prompt B — DB: Posts Tracking Felder hinzufügen (dbDelta + Versioning)
**Empfohlenes Modell:** GPT‑4.1 (0x)

Erweitere die bestehende Tabelle (laut Projekt: `ltl_saas_settings`):

Neue Spalten:
- `posts_this_month` INT NOT NULL DEFAULT 0
- `posts_period_start` DATE NULL

Wichtig:
- dbDelta SQL: jedes Feld in eigener Zeile
- Plugin DB-Version erhöhen (Option `ltl_saas_db_version`), damit Updates sauber laufen
- Backfill: Wenn `posts_period_start` NULL → setze auf aktuellen Monatsersten

---

## Prompt C — Reset + Enforce im /make/tenants Endpoint
**Empfohlenes Modell:** GPT‑4.1 (0x)

Im REST Endpoint `GET /wp-json/ltl-saas/v1/make/tenants`:

Für jeden Tenant:
1) Hole state über `ltl_saas_get_tenant_state($user_id)`
2) Reset-Check:
   - Wenn `posts_period_start` nicht der aktuelle Monatserste ist → setze `posts_this_month=0` und `posts_period_start=<aktueller Monatserster>`
3) Enforce:
   - Wenn `posts_this_month >= posts_limit_month`:
     - markiere Tenant im Response als `skip=true`
     - `skip_reason="monthly_limit_reached"`
     - OPTIONAL: `remaining=0`
   - sonst `skip=false`, `remaining = posts_limit_month - posts_this_month`

Wichtig:
- Make soll damit einfach „skips“ machen (kein Run).
- Endpoint liefert weiterhin Secrets nur mit Token + SSL-Check.

---

## Prompt D — Inkrement im /run-callback Endpoint
**Empfohlenes Modell:** GPT‑4.1 (0x)

Im Endpoint `POST /wp-json/ltl-saas/v1/run-callback`:

Wenn Callback ein “successful publish” signalisiert:
- Inkrementiere `posts_this_month` für den Tenant um 1

Details:
- Leite Tenant eindeutig ab (z.B. user_id/tenant_id aus Payload)
- Füge Schutz hinzu: wenn Monat gewechselt → erst resetten wie in Prompt C

Optional:
- Wenn posts_this_month nach Inkrement > limit → NICHT automatisch is_active=0 setzen.
  Nur das Limit soll greifen.

---

## Prompt E — Docs Update (Limits + Beispiele)
**Empfohlenes Modell:** GPT‑4o (0x)

Aktualisiere / erstelle:
- `docs/reference/api.md` (make/tenants response Felder: skip, remaining, posts_limit_month, posts_this_month)
- `docs/engineering/make/multi-tenant.md` (Make Flow: fetch tenants → for each tenant if skip then continue → run pipeline → callback)

Kurz & konkret, mit:
- Beispiel JSON (tenants list inkl. skip)
- 403/401/200 Verhalten

---

## Prompt F — Smoke Test + Commit + PR
**Empfohlenes Modell:** GPT‑5 mini (0x)

1) Folge `SMOKE_TEST_SPRINT_04.md`
2) Commit (max 3 Commits)
3) PR Beschreibung: enthält „Closes #16“
