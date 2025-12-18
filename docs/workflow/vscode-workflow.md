# VS Code Workflow (ohne GitHub Desktop) — LTL AutoBlog Cloud

Du willst **alles** in VS Code machen: Issues → Branch → Commit → Push → PR → Merge. Geht.

## 0) Setup (einmalig)
1) VS Code: **Source Control** öffnen (Icon links oder `Ctrl+Shift+G`).
2) Wenn VS Code fragt: **"Sign in to GitHub"** → einloggen.
3) Extensions (empfohlen):
   - **GitHub Pull Requests and Issues**
   - **GitHub Copilot**
   - (Optional) Conventional Commits Helper

> Die PR/Issues Extension erlaubt PRs + Issues direkt in VS Code zu managen.

## 1) Dein täglicher Loop (15 Minuten)
1) VS Code: Sidebar → **GitHub** / **Pull Requests** / **Issues** (über die PR/Issues Extension).
2) Filter: `label:mvp is:open` (oder nimm Milestone M2/M3 als Fokus).
3) Öffne ein Issue → lies “Done Definition” → arbeite nur daran.

## 2) Branch pro Issue (sauber & einfach)
- Unten links in der Status Bar auf Branch klicken → **Create new branch**
- Name-Vorschlag (copy/paste):
  - `m2-wp-connect-10`
  - `m3-active-users-12`
  - `fix-401-wp-connect-10`

> Dein aktueller Branch `Phase1-Core` ist ok. Für später: pro Issue ein Branch ist am entspanntesten.

## 3) Stage → Commit (Source Control View)
1) `Ctrl+Shift+G`
2) Changes anschauen → bei Dateien auf **+** (Stage) klicken
3) Commit Message oben eintippen:
   - `type(scope): kurz`
   - Beispiele: `feat(portal): add settings ui`
4) **Commit** klicken

### Commit “Training Wheels”
- Stage bewusst (damit du lernst)
- Dann commit

## 4) Push / Sync
- Source Control: **Sync Changes** (oder Push)
- Wenn du die Sync-Confirmation nervig findest: `git.confirmSync` in Settings.

## 5) Pull Request direkt in VS Code
- PR/Issues Extension → **Create Pull Request**
- In die PR Description **eine Zeile** setzen:
  - `Closes #10`
  - oder `Fixes #11`
- Merge (wenn Checks grün) → Issue wird automatisch geschlossen.

## 6) Wenn du Commit-Messages “vergisst”
Nutze Copilot als Commit-Coach:
- Staged changes auswählen
- Lass Copilot aus den staged changes eine kurze Commit Message vorschlagen
- Copy/Paste in die VS Code Commit Box

## 7) Mini-Standard (für’s Gehirn)
Types: `feat`, `fix`, `docs`, `chore`, `refactor`
Scopes: `portal`, `make`, `billing`, `security`, `docs`, `ci`

Fertig. Mehr brauchst du erstmal nicht.
