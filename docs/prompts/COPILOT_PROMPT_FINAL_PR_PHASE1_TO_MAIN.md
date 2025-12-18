# COPILOT_PROMPT_FINAL_PR_PHASE1_TO_MAIN.md

**Empfohlenes Modell:** GPT-5 mini (0x) für Text + GPT-4.1 (0x) für „was ist drin“.

---

Du bist Maintainer. Ziel: Erstelle eine **Final PR** von `Phase1-Core` nach `main`.

## Input, den du dir aus dem Repo holen sollst
- Liste der Commits in `Phase1-Core` seit letztem `main`
- Welche Issues sind wirklich vollständig umgesetzt?

## Output
1) PR Title (kurz, klar)
2) PR Description:
   - Summary (Bulletpoints)
   - Testing (was wurde manuell getestet)
   - Risk / Notes
   - **Closing keywords** nur für Issues, die wirklich fertig sind: `Closes #9`, etc.
3) Optional: Release Notes Draft (kurz)

**Wichtig:** Kein Auto-Merge. Nur Text.
