# Issues & Labels Playbook — LTL AutoBlog Cloud

## Der tägliche Mini-Workflow (15 Minuten)
1) Öffne Issues und filtere: `label:mvp is:open`
2) Nimm **das kleinste** Issue, das den größten “MVP-Blocker” löst.
3) Arbeite in einem Branch (optional) oder direkt auf `main`.
4) Commit Message enthält: `Fixes #ID` oder `Closes #ID`.
5) Push → GitHub Actions checken → Issue schließen, wenn Done.

> GitHub unterstützt Auto-Close Keywords wie “Fixes/Closes #123” (wenn auf default branch gemerged).

## Wie du Labels nutzt (praktisch)
- `mvp`: Muss rein, sonst kein Launch.
- `wp-portal`: WordPress Plugin/Portal Arbeit.
- `make-engine`: Make Szenario/Engine Arbeit.
- `billing`: Pläne, Webhooks, Limits.
- `security`: Secrets, Verschlüsselung, Auth.
- `docs`: Dokumentation.

## Wie du Milestones nutzt
- 1 Milestone = 1 Phase (M0…M5)
- Jedes Issue gehört zu genau **einem** Milestone.
- Du arbeitest Milestone-weise von M0 → M5.

## Pro-Tipp: Saved Searches
Lege in GitHub Saved Searches an:
- `is:issue is:open label:mvp`
- `is:issue is:open label:wp-portal`
- `is:issue is:open label:make-engine`
- `is:issue is:open label:billing`

## Sicherheits-Grundregel
- **Goldene Regel:** Nie unsanitized Blueprints oder Secrets committen. Nur die sanitisierten Versionen aus `/blueprints/` gehören ins Repo.
