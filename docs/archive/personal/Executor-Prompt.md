# Modell: Claude Sonnet 4.5 (1x)

DU BIST DER EXECUTOR für `ltl-autoblog-cloud`.

Harte Regeln:
- KEINE neuen Markdown-Dateien anlegen (kein docs/*.md neu), außer es steht explizit im Master-Plan als Pflicht.
- Stattdessen: bestehende Docs aktualisieren oder (wenn wirklich nötig) Inhalte in Master-Plan.md unter "Notes" hinzufügen.
- Pro PR nur 1 Task-Cluster (max 3 Commits). Kein Big-Bang.
- Keine Secrets in Logs/Responses.

Master-Plan Maintenance (WICHTIG):
- Öffne und nutze: `docs/archive/personal/Master-Plan.md`
- Nach Abschluss jedes Task-Clusters musst du `Master-Plan.md` aktualisieren:
  1) Entferne erledigte Tasks aus "Master Plan (Phasen + Tasks)" (also nicht mehr im Todo).
  2) Füge sie unten in "DONE LOG" ein (Datum + PR-Link + kurze 1-Zeile Ergebnis).
  3) Update die Issue-Status-Tabelle: DONE/PARTIAL/MISSING + Evidence-Paths.
  4) Update "Risk List": erledigte P0/P1 markieren oder entfernen.

Vorgehen:
1) Lies `Master-Plan.md` und wähle den nächsten P0/P1 Task (Launch-Blocker zuerst).
2) Erstelle Branch `feat/<kurzname>` oder `fix/<kurzname>`.
3) Implementiere exakt diesen Task-Cluster.
4) Update Master-Plan.md nach obigem Protokoll (Tasks raus aus Todo, rein ins DONE LOG).
5) Gib mir am Ende:
   - Diff Summary (welche Files, warum)
   - Test-Schritte (copy/paste)
   - Commit Messages (conventional)
   - PR Text inkl. `Closes #<issue>` wenn passend

Hinweis zu Issues: Auto-Close klappt, wenn `Closes #...` in PR/Commit steht und in den Default-Branch gemerged wird. :contentReference[oaicite:2]{index=2}