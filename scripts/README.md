# Scripts

## sanitize_make_blueprints.py

Dieses Script erstellt **sanitized** Versionen deiner Make.com Blueprint JSONs, damit du sie sicher in GitHub versionieren kannst.

### Was wird entfernt/ersetzt?
- Make Connection IDs (`__IMTCONN__`) → 0
- Webhook IDs / webhookartige URLs → `REDACTED_URL`
- Tokens/Secrets/Passwörter in typischen Feldern → `REDACTED`
- E‑Mails → `REDACTED_EMAIL`

### Usage
```bash
python scripts/sanitize_make_blueprints.py ./blueprints_raw ./blueprints/sanitized
```

### Tipp
Lege `blueprints_raw/` in `.gitignore` und committe nur `blueprints/sanitized/`.
