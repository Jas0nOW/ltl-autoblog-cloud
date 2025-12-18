# Sprint — Issue #19: Pricing + Landing Page (1 Seite, sauber)

**Issue Link:** https://github.com/Jas0nOW/ltl-autoblog-cloud/issues/19

## Ziel (DoD)
- Eine **klare 1‑Seiten Landing Page** (Value Props + Planvergleich + FAQ mini).
- CTA: **„Starten“ → Checkout** (pro Plan ein Link).
- Besucher versteht in **≤ 30 Sekunden**, was er bekommt (Issue-Definition).
- Copy liegt als Markdown **und** es gibt eine einfache WordPress-Integration (Shortcode oder Copy‑Paste HTML).

## Empfohlenes Modell
- **Copy + Struktur:** GPT‑4o (0x)
- **WP Shortcode / Settings:** GPT‑4.1 (0x)

---

## Prompt A — Landing Copy (DE) + Planvergleich
**Modell:** GPT‑4o (0x)

Erstelle in `/docs/marketing/`:
1) `LANDING_PAGE_DE.md`:
- Hero (1 Satz Value Prop + Subline)
- 3–5 Bullet Benefits (konkret, nicht fluff)
- “So funktioniert’s” (3 Steps)
- Planvergleich (Basic/Pro/Studio) basierend auf `docs/PRICING_PLANS.md` (falls vorhanden; ansonsten generisch mit klaren Limits)
- Mini‑FAQ (5 Fragen)
- CTA Text + Hinweis “Du brauchst nur WordPress + RSS Quelle”
2) `LANDING_PAGE_EN.md` (gleiche Struktur, US‑tauglich)

Output soll so sein, dass ich es 1:1 in eine WordPress Seite kopieren kann.

---

## Prompt B — Checkout Links als Settings (Admin)
**Modell:** GPT‑4.1 (0x)

Im WP Portal Plugin:
- Füge Settings hinzu (z.B. “Marketing/Billing”):
  - `checkout_url_basic`
  - `checkout_url_pro`
  - `checkout_url_studio`
- Sanitize: `esc_url_raw`, only https
- UI: Input fields + “Test link” hint

---

## Prompt C — Shortcode `[ltl_saas_pricing]` (public)
**Modell:** GPT‑4.1 (0x)

Implementiere einen public Shortcode:
- `[ltl_saas_pricing]` rendert Landing Page (Hero + Cards + Plan table + CTA buttons)
- CTA Buttons nutzen die Settings-URLs
- Minimal-CSS “scoped” (nur innerhalb Container), kompatibel mit Dark Mode
- Optional: Parameter `lang="de|en"`

Akzeptanz:
- Ohne Login sichtbar
- Keine PHP Notices
- Works auf mobile (simple stacking)

---

## Prompt D — Docs: Wie man die Seite live schaltet
**Modell:** GPT‑4o (0x)

Erstelle `docs/marketing/PUBLISH_LANDING.md`:
- WordPress Page erstellen (Slug z.B. /autoblog)
- Shortcode einfügen oder Copy aus `LANDING_PAGE_DE.md`
- Checkout Links setzen
- Test: Desktop + Mobile + incognito

---

## Prompt E — Smoke Test + PR
**Modell:** GPT‑5 mini (0x)

- `docs/testing/smoke/issue-19.md`:
  - Seite lädt ohne Login
  - Buttons zeigen auf korrekte Checkout URLs
  - Planvergleich sichtbar
- PR Text enthält: `Closes #19`
