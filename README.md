# LTL AutoBlog Cloud

Ein kleines SaaS‑Portal (WordPress) + eine Multi‑Tenant Make.com Engine:  
**Abo abschließen → RSS + Stil einstellen → WordPress verbinden → Auto‑Blog läuft.**

## Status
- **MVP Fokus:** Blog‑Bot als SaaS (AutoBlog Engine)
- **Add‑ons später:** Cross‑Promoter, Newsletter‑Bot, Video‑Bot, Watchdog/Sales intern

## Was ist in diesem Repo?
- **Docs** (Roadmap, Architektur, Onboarding, Pricing)
- **Sanitized Make Blueprints** (für Versionierung/Sharing – nicht 1:1 importierbar)
- **Scripts** (Blueprint Sanitizer)

> ⚠️ **Sanitized Blueprints** sind absichtlich von Connections/Webhooks/Secrets bereinigt.  
> Für echte Runs nutzt du deine originalen Blueprints in Make.com.

## Repo-Struktur
```
blueprints/
  sanitized/
docs/
scripts/
.github/workflows/
```

## Quick Start (Repo)
1. Lies die Roadmap: `docs/ROADMAP.md`
2. Lies die Architektur: `docs/ARCHITECTURE.md`
3. Lies das Onboarding: `docs/ONBOARDING.md`
4. Blueprints sanitizen:
   ```bash
   python scripts/sanitize_make_blueprints.py ./blueprints_raw ./blueprints/sanitized
   ```

## CI (GitHub Actions)
Dieses Repo nutzt einen kleinen Workflow, der Python-Skripte kompiliert (Smoke‑Check).  
Workflow-Dateien liegen unter `.github/workflows/`.

## Security / Geheimnisse
- Keine `.env`, Tokens, Webhook‑URLs, Passwörter committen.
- Nutze `.gitignore` und den Sanitizer.
- Wenn du was versehentlich gepusht hast: **History bereinigen** (lieber sofort fixen).

## Lizenz
Aktuell: **All rights reserved** (proprietär), bis du bewusst eine OSS‑Lizenz setzt.
