# Security Notes

- **Keine Secrets ins Repo** (Tokens, Webhooks, API Keys, Passwörter).
- Nutze `.gitignore` + Sanitizer.
- Wenn du versehentlich Secrets gepusht hast:
  1) Keys sofort rotieren
  2) Commit history bereinigen (z.B. via BFG / git filter-repo)
