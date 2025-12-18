# COPILOT_PROMPTS_SPRINT_07_GUMROAD_BILLING.md

> Ziel: Gumroad Ping → Tenant aktivieren & Plan setzen.

---

## Prompt A — Admin Settings: Gumroad Secret + Product-ID → Plan Mapping
**Empfohlenes Modell:** GPT‑4.1 (0x)

Erweitere die Admin-Seite um einen neuen Abschnitt “Billing (Gumroad)”:

1) Settings/Options:
- `ltl_saas_gumroad_secret` (string, keep secret)
- `ltl_saas_gumroad_product_map` (json/text) z.B.:
  {
    "prod_ABC123": "starter",
    "prod_DEF456": "pro",
    "prod_GHI789": "agency"
  }

2) UI:
- Secret Feld **maskiert** (nur letzte 4 Zeichen), Button “Generate new secret”
- Product Map als Textarea (JSON), mit “Validate JSON” hint (sanitize + wp_json_encode)
- Hilfe-Text: “Ping URL: https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping?secret=XXXX”

3) Speicherung:
- WP Settings API (register_setting)
- sanitize callbacks:
  - secret: trim, allow only url-safe characters
  - map: json_decode validate, re-encode pretty

Akzeptanz:
- Keine Secrets in Logs
- Wenn JSON invalid: Admin error notice, keine Speicherung

---

## Prompt B — REST Endpoint: Gumroad Ping
**Empfohlenes Modell:** GPT‑4.1 (0x)

Implementiere einen Endpoint:
- Method: POST
- Route: `/wp-json/ltl-saas/v1/gumroad/ping`

Gumroad sendet `application/x-www-form-urlencoded` Parameter. Du sollst mindestens auslesen:
- `email`
- `product_id`
- `subscription_id` (optional)
- `recurrence` (optional)
- `refunded` (string "true"/"false")
- `sale_id` (optional)

Security:
- Verlange `secret` Query param (oder Header) und vergleiche mit `ltl_saas_gumroad_secret`
- Wenn secret falsch/leer: 403
- Optional: wenn `!is_ssl()` → 403 (wir dokumentieren Proxy-Fall separat)

Response:
- IMMER schnell 200 mit `{ ok: true }` wenn verarbeitet,
  sonst 4xx/5xx (Gumroad retries, daher keine langen Prozesse)

Am Ende: Liste der geänderten Dateien + curl Beispiel.

---

## Prompt C — Provisioning: User & Settings upsert
**Empfohlenes Modell:** GPT‑4.1 (0x)

Wenn Ping valid:

1) User finden:
- Suche WP User per Email (`get_user_by('email', $email)`)

2) Falls nicht existiert:
- Erstelle neuen User:
  - user_login: aus Email ableiten (sanitize_user)
  - user_pass: random
  - role: subscriber (oder custom role wenn vorhanden)
- Sende Email (wp_mail) mit:
  - Login URL (wp_login_url)
  - Hinweis: “Passwort zurücksetzen” Link (wp_lostpassword_url)

3) Plan bestimmen:
- product_map aus Option lesen
- wenn product_id nicht gemappt: plan="free" oder "starter" (entscheidbar), plus Log/notice

4) Settings row upsert:
- Setze `plan` und `is_active=1`
- Speichere `gumroad_subscription_id` wenn vorhanden (neues Feld optional in settings table oder usermeta)

Akzeptanz:
- Wiederholte Pings für gleiche Email aktualisieren Plan sauber, ohne Duplikate.

---

## Prompt D — Refunded Handling
**Empfohlenes Modell:** GPT‑4.1 (0x)

Wenn `refunded=true`:
- Setze `is_active=0` für den Tenant
- Optional: schreibe `deactivated_reason='refunded'`

Wichtig:
- Kein Delete, nur deaktivieren (Support freundlich)
- Idempotent: mehrfaches refunded Ping kaputt macht nichts

---

## Prompt E — Docs: Gumroad Setup + Test Ping
**Empfohlenes Modell:** GPT‑4o (0x)

Erstelle:
- `docs/billing/gumroad.md`

Inhalt:
- Wo man bei Gumroad “Ping endpoint” setzt
- Warum HTTPS
- Beispiel Ping URL
- Welche Felder wir nutzen (email, product_id, refunded, subscription_id)
- Wie man “Send test ping to URL” nutzt und was man im WP Log sieht (ohne Secrets)

---

## Prompt F — Smoke Test + Commit + PR
**Empfohlenes Modell:** GPT‑5 mini (0x) für Commit/PR Text

1) Erstelle `docs/testing/smoke/sprint-07.md` mit:
- wrong secret → 403
- valid secret + new email → user created + active
- valid secret + existing user → plan updated
- refunded=true → is_active=0
2) Commit (max 3)
3) PR Text enthält: `Closes #17`
