# Modell: Claude Sonnet 4.5 (1x)
# ROLE: EXECUTOR (FULL PASS) für `ltl-autoblog-cloud`
# AUTORUN: TRUE — keine Rückfragen, keine Meta-Updates.

## Master-Plan Datei (auto-detect)
- Wenn `docs/archive/personal/Master-Plan.md` existiert: nutze die.
- Sonst nutze `Master-Plan.md`.

## Nicht verhandeln
- Du beginnst SOFORT mit `EXECUTION START`.
- Du arbeitest durch, bis in Phase 0/1/2 keine `Task:`-Blöcke mehr übrig sind.
- Keine “DONE” Tags in Tasks. Erledigte Tasks werden oben vollständig entfernt und NUR im DONE LOG geführt.

## Cluster-Regeln
- Pro Task-Block = 1 Cluster.
- Pro Cluster max 3 Commits (Code / Docs / Master-Plan Update).
- Branch pro Cluster: `fix/<kebab-taskname>` oder `feat/<kebab-taskname>`.
- Keine neuen Markdown-Dateien anlegen, außer Master-Plan fordert es explizit.

---

# EXECUTION START

## Schritt A — Queue bauen (deterministisch)
1) Öffne den Master-Plan.
2) Finde Abschnitt `## 4) Master Plan (Phasen + Tasks)`.
3) Parse ALLE `Task:`-Blöcke in Reihenfolge:
   - Phase 0 zuerst, dann Phase 1, dann Phase 2.
4) DONE-LOG Guard:
   - Wenn derselbe Task (Issue #X oder gleicher Task-Titel) bereits im DONE LOG existiert:
     - Dann gilt er als erledigt.
     - Falls er oben noch steht: entferne ihn oben (Cleanup), ohne neuen DONE-Log-Eintrag.

## Schritt B — Execute Loop (bis Queue leer)
Für jeden Task-Block der Queue:

### B1) Execute
- Erstelle Branch.
- Implementiere exakt die Punkte des Task-Blocks.
- Halte dich an “Files to touch”.
- Erfülle die DoD.
- Sammle Evidence (konkrete Dateipfade + kurze Test/Command Steps).

### B2) Master-Plan Update (immer letzter Commit)
1) Entferne den kompletten Task-Block aus der Phase-Liste.
2) Update `## 2) Open Issues Status`:
   - Wenn Issue referenziert wird: Status + Evidence + Test/Gaps korrekt setzen.
   - Wenn der Task erledigt ist: zu DONE wechseln und Gaps leeren/abschließen.
3) Update `## 3) Risk List`:
   - Wenn Task ein Risk adressiert: Risk entfernen oder eindeutig als erledigt markieren (nur im Risk-Abschnitt).
4) DONE LOG:
   - Füge GENAU 1 neuen Eintrag hinzu (nur wenn noch nicht vorhanden).

DONE LOG Template:
### [Issue #X oder Task-Titel] — [Task-Name] ✅
- **Date**: YYYY-MM-DD
- **Branch**: `fix/...` (commit: abc123d)
- **PR**: TBD
- **Result**: [1 Zeile]
- **Impact**: Phase 0/1/2 — [Launch Blocker/Reliability/Readiness]
- **Evidence**: [filepaths + 1–3 reproduzierbare Test-Commands]

### B3) Output nach jedem Cluster
- Diff Summary (welche Files + warum)
- Test-Schritte (copy/paste)
- Commit Messages (conventional)
- PR Text inkl. `Closes #<issue>` (wenn Issue existiert)

## Schritt C — Finale
- Wenn keine `Task:`-Blöcke mehr in Phase 0/1/2 existieren:
  - Ausgabe `ALL TASKS COMPLETED`
  - Kurze Liste: was im DONE LOG neu hinzugekommen ist.
