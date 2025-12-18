# COPILOT_PROMPTS_SPRINT_03_SECURITY_AND_ACCESS.md

> Ziel: Audit-Funde schließen: Access Control, Crypto HMAC, REST Secrets, Make Contract.

## Prompt A — Crypto Hardening: HMAC + Backwards Compatibility
**Empfohlenes Modell:** GPT‑4.1 (0x)

Implementiere in `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-crypto.php`:

1) Erweitere die bestehende AES-256-CBC Verschlüsselung um Integrität (HMAC-SHA256).
   - Nutze `hash_hmac('sha256', <data>, <mac_key>, true)`
   - `mac_key` separat aus WP Keys ableiten (z.B. hash('sha256', AUTH_KEY . SECURE_AUTH_KEY . 'mac', true))
   - `enc_key` wie bisher (oder getrennt ableiten)

2) Speicherformat (string):
   - `v1:<base64(iv)>:<base64(ciphertext)>:<base64(hmac)>`

3) Decrypt:
   - Wenn String mit `v1:` beginnt → parse → HMAC prüfen (timing-safe compare) → dann decrypt.
   - Wenn nicht `v1:` → **Legacy decrypt** (dein bisheriges Format), damit alte Daten nicht kaputt gehen.
   - Bei erfolgreichem Legacy decrypt: optional flag zurückgeben, damit beim nächsten Save re-encrypt in v1 passiert.

4) Stelle sicher:
   - Keine secrets in logs
   - Fehler sind “freundlich”: return WP_Error mit Message (ohne Secrets)

Am Ende: Liste der geänderten Dateien + kurzer Testplan.

---

## Prompt B — REST Secrets Split: safe endpoint vs make endpoint
**Empfohlenes Modell:** GPT‑4.1 (0x)

Ziel: Kein Endpoint soll “aus Versehen” decrypted passwords rausgeben, außer dem Make-Endpoint.

1) Prüfe `includes/REST/class-rest.php`:
   - Wenn es `GET /active-users` gibt, ändere es so, dass es **niemals** `wp_app_password` decrypted zurückgibt.
   - Stattdessen: `wp_app_password: "***"` oder Feld komplett weglassen.

2) Erstelle einen neuen Endpoint nur für Make:
   - `GET /wp-json/ltl-saas/v1/make/tenants`
   - Auth: Header `X-LTL-SAAS-TOKEN`
   - Token in WP Option `ltl_saas_make_token`
   - Wenn Token fehlt/leer/falsch: 403

3) Response enthält pro Tenant:
   - user_id/tenant_id, plan, is_active
   - settings: rss_url, language, tone, publish_mode, frequency
   - wp_url, wp_user
   - wp_app_password **decrypted** (nur hier)

4) Security:
   - `permission_callback` muss Token prüfen
   - Optional: Wenn `!is_ssl()` dann 403 (damit keine Secrets über http laufen)

Am Ende: Curl-Beispiele + Testplan.

---

## Prompt C — Admin Setting: Make Token konfigurieren
**Empfohlenes Modell:** GPT‑4.1 (0x)

Füge in der Admin-Seite (`includes/Admin/class-admin.php` oder passendes File) ein Setting hinzu:

- Feld: `ltl_saas_make_token`
- UI: “Make Token (keep secret)”
- Speicherung über WP Settings API (register_setting, sanitize)
- Zeige: “Token gesetzt” aber niemals Token im Klartext (optional: nur letzte 4 Zeichen)

Bonus:
- Button “Generate new token” (random bytes base64url, dann speichern)

---

## Prompt D — Access Control MVP (#11)
**Empfohlenes Modell:** GPT‑4.1 (0x)

Baue eine Minimal-Access-Control:

1) Datenmodell:
   - In `ltl_saas_settings` oder eigener Tabelle: Felder `plan` (string) und `is_active` (tinyint).
   - Default: `is_active=1` (für existing users) oder konfigurierbar.
   - DB Migration über dbDelta (activate + optional version check).

2) Dashboard:
   - Wenn `!is_active`:
     - Zeige Lock-Screen Box: “Abo erforderlich”
     - Link zur Pricing Seite (Option `ltl_saas_pricing_url` oder placeholder)

3) REST:
   - Alle Endpoints, die Settings speichern oder Make Secrets liefern, geben 403 wenn `!is_active`.

Akzeptanz:
- Inaktive User können Settings nicht speichern
- Inaktive User sehen nur Lock Screen
- Aktive User: alles normal

---

## Prompt E — Docs: API + Make Contract
**Empfohlenes Modell:** GPT‑4o (0x)

Erstelle/aktualisiere:
- `docs/api.md` (Endpoints, Auth, Beispiele)
- `docs/make-multi-tenant.md` (Scenario: Pull → Iterate → Publish → Callback)

Kurz, aber konkret:
- Beispiel JSON
- Curl Requests
- Fehlercodes (401/403/200)

---

## Prompt F — Smoke Test + Commit
**Empfohlenes Modell:** GPT‑5 mini (0x) für Commit/PR Text

1) Folge `SMOKE_TEST_CHECKLIST.md`
2) Stage → Commit
3) Nutze `COPILOT_PROMPT_COMMIT_AND_PR.md` für Commit Message/Description

