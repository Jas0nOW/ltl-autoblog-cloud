# Copilot-Modelle: Warum nicht immer GPT‑5.2? (Roadmap + Entscheidungslogik)

## Kurzfassung
Auch wenn **GPT‑5.2**, **Claude Sonnet 4.5** und **GPT‑5.1‑Codex‑Max** bei dir alle **1×** kosten, sind sie **nicht “gleich”**.  
Sie sind auf **verschiedene Aufgaben** optimiert. Wenn du das passende Modell nimmst, brauchst du oft **weniger Iterationen** → am Ende **schneller** und (praktisch) **günstiger**, obwohl die 1× gleich aussieht.

GitHub sagt explizit: Das Modell beeinflusst Qualität, Latenz und Task‑Performance – wähle **nach Aufgabe**, nicht nach Namen. Außerdem gibt es **Premium‑Multiplikatoren**, die den Verbrauch beeinflussen.

---

## 1) Was bedeuten 0× / 0.33× / 1× / 3×?
Copilot rechnet „Premium‑Requests“ nach **Interaktionen**, multipliziert mit einem **Model‑Multiplier**.

- **0×**: „Included models“ verbrauchen auf bezahlten Plänen **keine** Premium‑Requests (z. B. GPT‑4.1, GPT‑4o, GPT‑5 mini – je nach Plan/Policy).
- **0.33×**: günstiger/leichter – gut für Doku/kleine Tasks.
- **1×**: Standard – gute Allround‑Premium‑Modelle.
- **3×**: teuer – nur für seltene „Boss‑Fights“.

> Faustregel: Wenn ein 1×‑Modell den Job in **1 Durchlauf** schafft, während du mit einem anderen 1×‑Modell 4 Durchläufe brauchst, ist das „bessere“ Modell effektiv günstiger.

---

## 2) Warum Codex vs Sonnet vs GPT‑5.2? (vereinfacht)
### GPT‑5.2 (Reasoning/Planner)
**Stark in:** Planung, Architektur, Risikoabwägung, “Master‑Plan”, saubere Priorisierung.  
**Typische Nutzung:** Repo‑Audit, Master‑Plan, „was blockt Launch?“, Akzeptanzkriterien.

### GPT‑5.1‑Codex‑Max (Code‑Spezialist)
**Stark in:** Feature‑Implementierung, Bugfixes, saubere Code‑Änderungen in Produkt‑Repos.  
**Warum:** Codex wird als Software‑Engineering‑Agent/Model‑Fokus positioniert: Features schreiben, Bugs fixen, PR‑Vorschläge.  
**Typische Nutzung:** REST‑Endpoints, DB‑Änderungen, “Implementiere Issue #…”, Tests/Smoke‑Steps.

### Claude Sonnet 4.5 (Refactor/Agent‑Workhorse)
**Stark in:** Multi‑File‑Refactors, „Repo aufräumen ohne es zu zerstören“, größere Umstrukturierung + Konsistenz.  
**Typische Nutzung:** Code‑Cleanup, Struktur vereinheitlichen, „migrate / extract / rename“, große PRs in kleinen, sicheren Schritten.

### Claude Opus 4.5 (3×) = „Boss‑Fight“
Nur wenn du festhängst bei: komplexe Architektur‑Entscheidung, schwierige Security‑Threat‑Analyse, besonders knifflige Edge Cases.  
Nicht als Default.

---

## 3) Entscheider-Matrix (praktisch)
| Aufgabe | Bestes Default-Modell | Warum |
|---|---|---|
| Repo scannen, Plan machen, Prioritäten | **GPT‑5.2** | sehr stark im strukturieren & reasoning |
| Viele Dateien refactoren / Cleanup | **Sonnet 4.5** | stabil bei großen Code‑Bewegungen |
| Feature/Issue implementieren | **GPT‑5.1‑Codex‑Max** | „coding-first“, gut bei konkreter Umsetzung |
| Docs/README/Landing Copy | **GPT‑4o** (oder 0.33× Modelle) | schneller, guter Text/UX |
| Mini-Fixes, Formatierung, kleine Checks | **Haiku 4.5** / **Gemini Flash** | günstig, reicht oft |
| Security/Threat Modeling, wenn’s brennt | **Opus 4.5** (sparsam) | teuer, aber manchmal der schnellste „Durchbruch“ |

---

## 4) Dein Workflow (Premium optimal ausnutzen)
### Phase A — Scan/Plan (1 Prompt)
**Modell:** GPT‑5.2  
**Output:** `docs/archive/personal/Master-Plan.md`

### Phase B — Execute (mehrere PRs, aber klein)
**Modell:** Sonnet 4.5 ODER GPT‑5.1‑Codex‑Max  
**Regel:** pro PR nur 1 Task‑Cluster (max 3 Commits) + Smoke‑Test Steps.

### Phase C — Re-Audit (1 Prompt)
**Modell:** GPT‑5.2 oder Gemini 2.5 Pro  
**Output:** „Blocker-Liste“ + ggf. Mini‑Sprint 09.

---

## 5) Mini-Regeln, damit es „sofort läuft“
1) **Planer trennt Denken von Tun**: Erst Plan (ohne Code‑Änderungen), dann Umsetzung.  
2) **Executor macht kleine PRs**: Weniger Risiko, leichter debuggen.  
3) **Beweise statt Behauptungen**: Jede „Done“-Aussage braucht Filepath/Route/Function.  
4) **Weniger Doku-Spam**: Lieber bestehende Docs aktualisieren, alte nach `docs/archive/...` verschieben.  
5) **Premium sparen durch Genauigkeit**: Das beste Modell ist oft das, das **weniger Rückfragen** braucht.

---

## Quellen (für späteres Nachschlagen)
- GitHub Docs: *AI model comparison* (Modellwahl nach Task, Multipliers, Auto‑Selection)
- GitHub Docs: *Requests in GitHub Copilot* (Premium‑Requests & Multipliers, included models)
- GitHub Docs: *Supported AI models in GitHub Copilot*
- OpenAI: *Introducing Codex* (Codex als Software‑Engineering Agent/Model-Fokus)
- Anthropic: *Introducing Claude Sonnet 4.5* (Positionierung als starkes Coding/Agent‑Model)
