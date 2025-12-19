# Design Prompt: LTL AutoBlog Cloud Agency Design

> **Dieser Prompt dokumentiert, wie das professionelle Agency-Design für das LTL AutoBlog Cloud Plugin erstellt wurde. Nutze diesen Prompt für zukünftige Design-Anpassungen oder als Referenz für ähnliche Projekte.**

---

## 🎯 Design-Auftrag

```
Erstelle ein professionelles Agency-Design für ein WordPress SaaS-Plugin.
Das Plugin ist eine Cloud-basierte Blogging-Automation mit KI-Unterstützung.

Zielgruppe: Content Creator, Blogger, kleine Unternehmen, Marketing-Agenturen
Stil: Modern, Clean, Professional, Trust-worthy
```

---

## 📋 Vollständiger Prompt

```markdown
Ich entwickle ein WordPress Plugin mit folgenden Seiten:
1. Admin Settings Page (Backend)
2. Admin Design Page (Backend)
3. Customer Dashboard (Frontend Shortcode)
4. Pricing Page (Frontend Shortcode)

Erstelle ein vollständiges, konsistentes Agency Design System mit:

### Farbpalette
- Primary: Blau-Violett Gradient (#667eea → #5568d3)
- Success: Grün (#28a745)
- Error: Rot (#dc3545)
- Warning: Gelb (#ffc107)
- Text: Dunkelgrau (#1a1a2e)
- Background: Hellgrau (#f8f9fc)

### Komponenten
1. **Header mit Sprachumschalter**
   - Dashboard-Titel mit Icon (🚀)
   - Untertitel mit Beschreibung
   - Language Switcher (🇺🇸 EN | 🇩🇪 DE) rechts

2. **Progress Card (Setup-Fortschritt)**
   - 4 Schritte mit Checkmarks (✅ oder ⚠️)
   - Jeder Schritt: Icon + Titel + Status + Action Button
   - Abgerundete Karte mit Shadow

3. **Form Sections**
   - Section Header mit Icon + Titel
   - Labels mit Tooltips (ℹ️)
   - Input-Felder mit Help-Text darunter
   - 2-spaltige Layouts für Selects
   - Primary Button für Submit

4. **Alerts/Notifications**
   - Success (grüner Hintergrund)
   - Error (roter Hintergrund)
   - Mit Icon links

5. **Table für History**
   - Zebra-Stripes
   - Status Badges (success/error)
   - Collapsible Details

6. **Pricing Cards**
   - 4 Plans: Free, Basic, Pro, Studio
   - Pro als "Featured" hervorgehoben
   - Studio in dunklem Theme
   - Feature-Listen mit Checkmarks

### CSS Best Practices
- CSS Variables für alle Tokens (--ltlb-color-*, --ltlb-gap-*, etc.)
- BEM-ähnliche Klassennamen mit "ltlb-" Prefix
- Transitions für alle Hover-States
- Responsive Design (Mobile breakpoint: 768px)
- Box-shadows für Tiefe
- Subtile Animationen (fade-in, translate on hover)

### JavaScript Features
- Language Switcher mit Cookie-Speicherung
- Toggle für Runs-Tabelle
- Test-Buttons mit Loading States
- Form-Validation Feedback
```

---

## 🔧 Technische Implementierung

### 1. CSS-Dateistruktur

**admin.css** (~800 Zeilen)
- Design Variables
- Page Header
- Tabs Navigation
- Settings Sections
- Hero Boxes
- Language Switcher
- Buttons & Forms
- Responsive Styles

**frontend.css** (~1100 Zeilen)
- Design Variables (identisch)
- Dashboard Container
- Dashboard Header
- Progress Card
- Form Sections
- Alerts
- Tables
- Locked State
- Pricing Components
- Responsive Styles
- Animations

### 2. PHP Template-Struktur

```php
// Dashboard Shortcode
<div class="ltlb-dashboard">
    <div class="ltlb-dashboard-header">...</div>
    <div class="ltlb-alert">...</div>
    <div class="ltlb-progress-card">
        <div class="ltlb-step">...</div>
    </div>
    <div class="ltlb-form-section">...</div>
    <div class="ltlb-runs-section">...</div>
</div>

// Pricing Shortcode
<div class="ltlb-pricing">
    <div class="ltlb-pricing-header">...</div>
    <div class="ltlb-pricing-hero">...</div>
    <div class="ltlb-pricing-grid">
        <div class="ltlb-pricing-card">...</div>
    </div>
</div>
```

### 3. i18n Integration

```php
// Übersetzungsarray für Frontend
$translations = [
    'en' => [
        'dashboard_title' => 'LTL AutoBlog Cloud',
        'btn_save' => 'Save',
        // ... 50+ Strings
    ],
    'de' => [
        'dashboard_title' => 'LTL AutoBlog Cloud',
        'btn_save' => 'Speichern',
        // ... 50+ Strings
    ]
];

// Cookie-basierte Sprachauswahl
$lang = $_COOKIE['ltl_frontend_lang'] ?? 'en';
```

### 4. JavaScript Language Switcher

```javascript
document.querySelectorAll('.ltlb-lang-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const lang = this.getAttribute('data-lang');
        document.cookie = `ltl_frontend_lang=${lang};path=/;max-age=${86400*365}`;
        window.location.reload();
    });
});
```

---

## 🎨 Design-Entscheidungen

| Entscheidung | Begründung |
|--------------|------------|
| Gradient statt Flat Color | Moderner Look, Brand Recognition |
| Card-basiertes Layout | Klare Struktur, Scanning-freundlich |
| Emoji-Icons | Universell, keine Icon-Library nötig |
| Helle Backgrounds | Professionell, weniger aggressive |
| Subtle Shadows | Tiefe ohne Überladung |
| 4px Grid System | Konsistentes Spacing |
| CSS Variables | Maintainability, Theme-fähig |

---

## 📐 Responsive Strategy

```
Desktop (>768px):
- 2-spaltige Forms
- 4-spaltige Pricing Grid
- Header horizontal

Tablet/Mobile (≤768px):
- 1-spaltige Forms
- Pricing Cards stacked
- Header vertikal zentriert
- Kleinere Font-Sizes
```

---

## ✅ Checkliste für neue Komponenten

- [ ] CSS Variables verwendet (keine Hardcoded Values)
- [ ] BEM-Namenskonvention mit `ltlb-` Prefix
- [ ] Hover/Focus States definiert
- [ ] Transitions für Animationen
- [ ] Responsive Styles hinzugefügt
- [ ] In Design System dokumentiert
- [ ] i18n Strings hinzugefügt

---

## 🔗 Dateien

| Datei | Beschreibung |
|-------|--------------|
| `assets/admin.css` | Admin Backend Styles |
| `assets/frontend.css` | Frontend Shortcode Styles |
| `docs/engineering/design-system.md` | Design System Dokumentation |
| `includes/Admin/class-admin.php` | Admin PHP mit Design |
| `includes/class-ltl-saas-portal.php` | Frontend Shortcodes |

---

## 📝 Beispiel-Anweisung für Copilot

```
Füge einen neuen Status-Badge zum Dashboard hinzu.

Verwende die Design Tokens aus dem LTL Design System:
- Farbe: --ltlb-color-warning
- Radius: --ltlb-radius-full
- Padding: --ltlb-gap-xs horizontal, --ltlb-gap-sm vertical
- Font: 0.75rem, weight 700

Klasse: .ltlb-badge-warning
```

---

> **Erstellt:** Dezember 2025
> **Erstellt von:** GitHub Copilot (Claude Opus 4.5)
> **Projekt:** LTL AutoBlog Cloud Portal Plugin
