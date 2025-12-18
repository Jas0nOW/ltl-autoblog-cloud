# Modell: GPT-5.2 (1x)

Du bist ein Principal Engineer. Scanne das gesamte Repo `Jas0nOW/ltl-autoblog-cloud` vollständig.

WICHTIG:
- In diesem Schritt KEINE Code-Änderungen (kein Refactor, keine Bugfixes).
- Du darfst aber EINE Datei erstellen/aktualisieren:
  `docs/archive/personal/Master-Plan.md`
  (Ordner anlegen, falls nicht vorhanden)

KONTEXT / ZIEL:
- Kunden schließen Abo ab, loggen sich ein, verbinden ihre WordPress-Seite, konfigurieren RSS+Sprache+Ton+Frequenz+Draft/Publish.
- Make Multi-Tenant Loop publiziert Posts auf ihre WP-Seite.
- Portal (WP Plugin) liefert Settings/Secrets/Endpoints; Make ruft ab; Callback schreibt Runs/Zähler.
- Dein Job hier: professioneller Review + Master-Plan bis “Launch Ready V1”.

AUFGABEN (Pflicht):
1) Architektur & Datenfluss erklären:
   - Portal UI → Speicherung → REST Endpoints → Make → Callback/Runs → Limits/Plans
   - Jede Aussage mit Belegen: konkrete Filepaths/Classes/Functions/Routes/DB tables.

2) Issue-Mapping:
   - Prüfe alle OPEN Issues im Repo.
   - Für jedes Issue: Status = DONE / PARTIAL / MISSING
   - Belege Status mit Pfaden/Endpoints/SQL/Docs.
   - Wenn DONE: 2–5 Teststeps.
   - Wenn PARTIAL/MISSING: konkrete Lückenliste + betroffene Files.

3) Professional/Production Checklist:
   - Security: Secrets, Encryption at rest, Auth/permission_callback, Input validation, Abuse surface.
   - Reliability: retries/backoff (Make/HTTP), idempotency (callback), month rollover reset, tenant isolation.
   - DX/Docs: Setup, onboarding, pricing, troubleshooting, release packaging (zip), changelog, smoke tests.
   - Repo hygiene: doppelte/obsolete Docs, leere Stubs, Naming-Konsistenz.

OUTPUT (als Datei + Kurzsummary im Chat):
A) Erzeuge/aktualisiere die Datei `docs/archive/personal/Master-Plan.md` mit exakt dieser Struktur:

# Master Plan — LTL AutoBlog Cloud (V1 Launch)
## 1) Current State Snapshot (max 20 bullets, jeder Bullet mit Evidence-Pfad)
## 2) Open Issues Status (Tabelle: Issue | Status | Evidence | Test/Gaps)
## 3) Risk List (P0/P1/P2, jeweils konkrete Fix-Idee + Pfade)
## 4) Master Plan (Phasen + Tasks)
- sortiert: Launch-Blocker zuerst
- pro Task: Goal, Files to touch, DoD, Impact (High/Med/Low), geschätzte Komplexität (S/M/L)
- Cleanup: bevorzugt MOVE nach docs/archive statt Delete

B) Zusätzlich im Chat:
- 10 Zeilen “Executive Summary” (was ist fertig, was blockt Launch am meisten)

REGELN:
- Keine vagen Aussagen. Jede Behauptung muss Evidence haben (Pfad/Route/Class).
- Keine neuen Dateien außer `docs/archive/personal/Master-Plan.md`.
- Kein Code ändern.
