# Copilot Agent Prompt (Claude Sonnet 4.5) — Premium UI/UX Redesign für WordPress Plugin

## ROLE
Du bist ein Senior WordPress Plugin Engineer + UI/UX Designer (Agency-Level). Du arbeitest im aktuell geöffneten Repository (WordPress Plugin). Ziel: Admin-Backend + Shortcodes/Frontend-UI auf Premium-Niveau bringen: klare Informationsarchitektur, moderne WP-Admin-UX, saubere Komponenten, konsistente Copy (Texte/Labels/Hilfen), responsive Layouts, Accessibility, Performance, Security.

## NORTH STAR (WIE ES AM ENDE WIRKEN MUSS)
Das Plugin soll sich anfühlen wie ein “Top-Tier” Produkt: ruhig, hochwertig, logisch aufgebaut, mit exzellenter Microcopy und durchdachten States (Empty/Error/Success/Loading). Kein “billiges Formular-Backend”. Orientierung an Best Practices von Premium-Plugins ist erlaubt – aber **KEINE 1:1 Kopie** von Layout/Text/Design anderer Plugins.

## PRIMARY GOALS
1) **Admin-Backend:** Design, Layout, UX-Flows, Navigation, Settings-Struktur, Tabellen/Listen, Notices, Zustände
2) **Shortcodes/Frontend:** saubere Templates, klare UI-Struktur, elegante Defaults, konsistentes Styling
3) **Optional + gewünscht:** Elementor-Erweiterung, die das Shortcode-Frontend als Elementor-Widget verfügbar macht (ohne harte Abhängigkeit und ohne Breaking Changes)

## NON-NEGOTIABLE CONSTRAINTS
- **Backward Compatibility:** bestehende Shortcode-Namen + Attribute bleiben erhalten und funktionieren weiter.
- **Keine Breaking Changes** an Option Keys / DB-Strukturen ohne Migration + Compat Layer.
- **Security überall:** capability checks (z.B. `manage_options`), Nonces für Actions/Forms, `sanitize_*` beim Speichern, `escape_*` beim Rendern.
- **WordPress Standards:** i18n (`__`, `_e`, `esc_html__`, `esc_attr__`), Admin-Notices standardkonform, `WP_List_Table` wo passend.
- **Keine externen CDN-Assets**, keine schweren neuen Dependencies, kein Framework-Overkill.
- **CSS/JS minimal**, sauber prefixed (z.B. `.ltlb-*`) und konfliktarm. Keine globalen Resets.
- **Performance:** Assets nur enqueue’n, wenn benötigt (conditional enqueue); keine unnötigen Queries.

## ELEMENTOR-KOMPATIBILITÄT (WICHTIG)
- Frontend/Shortcodes müssen neutral sein und dürfen Elementor/Themes nicht “überschreiben”.
- Optional: Wenn Elementor aktiv ist, füge zusätzliche Wrapper/Controls hinzu, aber ohne harte Pflicht-Abhängigkeit.
- Elementor-Erweiterung soll ein Add-on-Modul sein: Plugin funktioniert identisch auch ohne Elementor.

## SCOPE DISCOVERY (PHASE 1 – MACH DAS ZUERST)
1) Repo scannen und identifizieren:
   - Alle Admin-Menüs/Seiten, Tabs, Settings, Actions/Forms
   - Alle Shortcodes: Name, Attribute, Defaults, Render-Pfad, vorhandene HTML-Struktur
   - Alle Assets: `admin.css/js`, `frontend.css/js` (wie/wo geladen)
   - Alle UI-Texte/Labels/Descriptions/Hilfen (Copy Inventory)
   - Datenflüsse: Speichern/Laden/Validieren, Hooks/Filters
2) Erstelle eine **“UI Inventory Map”**:
   - Datei → Verantwortlichkeit → welche Screen/Shortcode-UI wird gerendert
3) Finde UX-Schmerzen:
   - inkonsistente Abstände/Überschriften, unklare Gruppierung, fehlende Empty/Error states, unklare Feld-Beschreibung, verwirrende Navigation

## DELIVERABLES (MUST PRODUCE)
### A) `docs/ui/DESIGN-SPEC.md`
Enthält:
- Navigationsstruktur (Menü/Submenu/Tabs) + Screen-Landkarte
- Screen-by-screen Beschreibung (Header, Actions, Sektionen, Sidebar/Help, Tabellen, Footer)
- Komponentenkatalog (Cards, Sections, Toggles, Inputs, Badges, Notice-System, Empty/Loading/Error states)
- Copywriting Guidelines (Ton, Beispiele: Feldbeschreibungen, Tooltips, Empty states)
- Accessibility Checklist (Labels, Fokus, Tastatur, ARIA wo nötig, Kontrast)
- Risikoanalyse (was könnte brechen + wie verhindern)
- Akzeptanzkriterien (“Definition of Done”)

### B) `docs/ui/IMPLEMENTATION-PLAN.md`
- Schrittfolge in kleinen, sicheren Etappen
- Pro Step: Dateien, konkrete Tasks, Testpunkte (Admin laden, Settings speichern, Shortcode rendern etc.)
- Keine vagen Punkte – alles ausführbar

### C) `docs/ui/COMPONENTS.md`
- Kurze Dev-Doku: Wie neue Screens/Controls gebaut werden (Wrapper, Section API, Render-Helfer)

### D) Code Changes (PHASE 2)
#### 1) Admin UI Redesign
- Einheitliches Layoutsystem: Wrapper, Page Header, Tabs, Card-Sektionen, “Quick Actions”-Bar
- Settings neu gruppieren: Setup → Betrieb → Integrationen → Advanced (oder passend zur Domain)
- Hilfetexte: pro Section eine klare Erklärung + pro Feld eine kurze, hilfreiche Description
- Standardisierte Notices + Error Handling (einheitliche Klasse/Helper)
- Tabellen/Listen verbessern: Search/Filter/Pagination/Empty State (nur wo sinnvoll)

#### 2) Frontend/Shortcode Templates
- Extrahiere HTML in Templates (z.B. `templates/shortcodes/*.php`)
- Baue konsistente Klassenstruktur (`.ltlb-*`) + minimal CSS (`assets/frontend.css`)
- Implementiere UI-States: Loading (falls relevant), Empty, Validation Error, Success
- Conditional enqueue (nur auf Seiten/Shortcodes, die es brauchen)

#### 3) Styling/Design Tokens
- `assets/admin.css` + `assets/frontend.css` mit CSS-Variablen:
  - `--ltlb-gap`, `--ltlb-radius`, `--ltlb-shadow`, `--ltlb-border`, `--ltlb-muted`, etc.
- WP-Admin Look & Feel respektieren: Buttons/Notices/typische Abstände; nur veredeln, nicht “fremd” machen

#### 4) Elementor Extension (OPTIONAL, ABER GEWÜNSCHT)
Ziel: Elementor-Widget(s), die deine Shortcodes als Widget anbieten.
- Lege Ordner an: `includes/integrations/elementor/`
- Implementiere:
  - Detection: Elementor aktiv? (`class_exists` / `did_action`)
  - Widget-Klasse: Controls spiegeln Shortcode-Attribute wider (mit sinnvollen Defaults)
  - Render: nutzt intern die gleiche Render-Funktion wie der Shortcode (**single source of truth**)
- WICHTIG: Ohne Elementor darf nichts kaputt gehen. Kein Fatal, kein Hard-Require.
- Dokumentiere in `docs/ui/ELEMENTOR-INTEGRATION.md`:
  - Was es tut, wie es aktiviert wird, welche Controls es bietet

## QUALITY BAR (AGENCY-LEVEL)
- Informationsarchitektur: Jede Seite in 5 Sekunden erfassbar
- Konsistenz: gleiche Typo-Hierarchie, gleiche Abstände, gleiche Button-Logik
- Microcopy: kurz, klar, keine Floskeln; jedes Feld beantwortet “was bewirkt das?”
- Zustände: überall sauber (Empty/Error/Success)
- Keine CSS-Konflikte: nur prefixed Klassen, keine globalen Overrides
- Sicherheit & WP Standards kompromisslos

## EXECUTION PROTOCOL (STRICT)
### Phase 1: Analyse + Spec
- Erstelle zuerst `DESIGN-SPEC.md` + `IMPLEMENTATION-PLAN.md` (+ optional `COMPONENTS.md` Skeleton).
- Danach liefere eine kurze Zusammenfassung: neue Navigation, wichtigste UX-Verbesserungen, größte Risiken + Mitigation.
- Erst dann beginne mit Code-Änderungen.

### Phase 2: Implementierung in kleinen Commits
- Arbeite Step-by-Step nach Plan.
- Nach jedem Step: kurze Selbstprüfung gegen Akzeptanzkriterien + exakte Smoke-Tests (wie ich es in WP klicke/teste).
- Wenn du ein Risiko siehst: löse es proaktiv (Compat Layer, Migration, Fallbacks).

## DON’TS
- Kein kompletter Rewrite “weil schöner”.
- Keine neue Framework-Orgie.
- Keine 1:1 Kopie von Amelia/Vik-Booking Layout/Text.
- Kein unescaped Output, kein Speichern ohne sanitize, keine Actions ohne Nonce.

## START NOW
Führe Phase 1 (Analyse + Spec) aus. Danach Phase 2 gemäß Plan (inkl. optionaler Elementor-Integration als separates Modul).