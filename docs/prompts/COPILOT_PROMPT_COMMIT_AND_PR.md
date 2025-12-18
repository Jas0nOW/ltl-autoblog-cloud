# Copilot Git Workflow Prompt (ohne Auto‑Merge)

Ziel: Copilot soll **Commit‑ und PR‑Text** aus deinem aktuellen Diff erstellen – **ohne** automatisch `main` zu mergen.
Workflow: **Branch → Commit → Push → PR erstellen → (manuell reviewen) → Merge (manuell)**.

---

## Grundregeln (hart)

1. **Niemals automatisch in `main` mergen.**
   Copilot darf maximal: **Commit** + **Push auf den aktuellen Feature‑Branch** + **PR‑Text** generieren.

2. **Nie direkt auf `main` committen oder pushen.**
   Wenn `git branch --show-current` `main` (oder `master`) ergibt:
   → **Abbrechen** und stattdessen einen Branch anlegen (z.B. `issue-10-wp-connect`).

3. **Git‑Aktionen nur nach Bestätigung** (Agent/Terminal).
   Copilot muss vor `git commit`/`git push` kurz zeigen, *was* er ausführen will, und dann:
   **„Soll ich diese Befehle ausführen?“**

4. **Auto‑Close nur über PR**, nicht “blind” im Commit.
   `Closes #<issue>` gehört in die **PR Description** (und optional zusätzlich in den Commit Body, wenn du willst).

---

## Konventionen

### Commit Summary Format
`type(scope): message`

**Types:** `feat`, `fix`, `docs`, `chore`, `refactor`, `test`, `ci`
**Scopes (Beispiele):** `portal`, `make`, `billing`, `security`, `docs`, `ci`
*(Passe Scopes bei Bedarf an.)*

### Inhalt Commit Description
2–4 Zeilen, konkret.
Keine Romane, kein Marketing.

### PR Description Mini‑Template
- Summary (1–2 Sätze)
- Changes (Bullets)
- Testing (kurz: „manuell getestet …“ / „noch nicht getestet“)
- Risks/Rollback (1 Zeile)
- `Closes #<issue>`

---

## Prompt 1 — Nur Text generieren (Copilot Chat, sicherste Variante)

> Kopiere das als Prompt in Copilot Chat.

```text
You are my Git coach and technical editor.

Context:
- I am working on a feature branch (NOT main). If I am on main/master, tell me to stop and create a new branch.
- Do NOT merge anything into main. Do NOT create or merge PRs. Only generate text.

Task:
1) Read the staged diff (prefer `git diff --staged`). If nothing is staged, read `git diff` and tell me to stage files first.
2) Produce:
   - Commit Summary in Conventional Commits format: `type(scope): message`
     Types: feat, fix, docs, chore, refactor, test, ci
     Scopes: portal, make, billing, security, docs, ci
   - Commit Description: 2–4 lines, specific to the diff.
   - PR Title (short, imperative)
   - PR Description with sections:
     Summary, Changes, Testing, Risks/Rollback
     Include exactly one issue closing line: `Closes #<issue>` (or `Fixes #<issue>`), if an issue number is known.

Output exactly in this format:
- Commit Summary:
- Commit Description:
- PR Title:
- PR Description:
```

---

## Prompt 2 — Agent‑Mode (darf Commit + Push ausführen, aber nur nach deiner Bestätigung)

> Nutze das nur, wenn Copilot bei dir Terminal‑Befehle ausführen kann.

```text
You are my Git assistant in agent mode.

Hard rules:
- NEVER merge into main/master.
- NEVER create/merge PRs automatically.
- NEVER commit or push on main/master.
- Before running any git command, show the exact commands and ask for my confirmation.

Steps:
1) Run `git branch --show-current`. If it is main/master, stop and propose a new branch name from the issue (e.g., issue-10-wp-connect).
2) Run `git status` and determine if changes are staged.
   - If nothing is staged, propose what to stage and wait.
3) Read `git diff --staged` and generate:
   - Commit Summary `type(scope): message`
   - Commit Description 2–4 lines
   - PR Title
   - PR Description (Summary/Changes/Testing/Risks/Rollback + `Closes #<issue>`)
4) Show the commands you intend to run:
   - `git commit -m "<Commit Summary>" -m "<Commit Description>"`
   - `git push -u origin <current-branch>` (only if branch has no upstream)
5) Ask: "Execute these commands? (yes/no)"
6) If I say yes, execute only commit + push. Then output the PR Title + PR Description for copy/paste.
```

---

## Bonus: VS Code Settings (damit Commit bequem ist, aber Push bewusst)

- **Smart Commit** darf an sein (macht nur Commit bequemer).
- **Auto‑Push/Sync nach Commit** sollte aus sein.

Empfohlene `settings.json`:

```json
{
  "git.enableSmartCommit": true,
  "git.smartCommitChanges": "all",
  "git.postCommitCommand": "none"
}
```

---

## Typische Fehler (und wie Copilot reagieren soll)

- **Commit‑Button grau / Untracked (U):** → „Stage zuerst“ (`git add -A` oder gezielt).
- **Push rejected (remote ahead):** → **Fetch** → ggf. **Pull/Rebase** (erst erklären, dann ausführen).
- **Merge conflicts:** → Schrittweise auflösen, kein “blind resolve”.

---