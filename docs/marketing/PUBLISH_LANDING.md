# Issue #19: Pricing Landing Page — Implementierung

## Prompt B: Checkout Links Settings

### Admin-Einträge erforderlich

**Datei zu ändern**: [wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php](../../wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php)

**Neue Konstanten am Anfang der Klasse**:
```php
const OPTION_CHECKOUT_URL_STARTER = 'ltl_saas_checkout_url_starter';
const OPTION_CHECKOUT_URL_PRO = 'ltl_saas_checkout_url_pro';
const OPTION_CHECKOUT_URL_AGENCY = 'ltl_saas_checkout_url_agency';
```

> **Hinweis**: Die Option Keys sind aus Kompatibilitätsgründen historisch benannt.
> - `..._starter` wird im UI als **Basic** verwendet
> - `..._agency` wird im UI als **Studio** verwendet

**In `register_settings()` hinzufügen**:
```php
register_setting(
    'ltl_saas_portal_settings',
    self::OPTION_CHECKOUT_URL_STARTER,
    array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'show_in_rest' => false,
        'default' => '',
    )
);
register_setting(
    'ltl_saas_portal_settings',
    self::OPTION_CHECKOUT_URL_PRO,
    array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'show_in_rest' => false,
        'default' => '',
    )
);
register_setting(
    'ltl_saas_portal_settings',
    self::OPTION_CHECKOUT_URL_AGENCY,
    array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'show_in_rest' => false,
        'default' => '',
    )
);
```

**In `render_admin_page()` Form-Abschnitt hinzufügen** (nach Billing section):

```php
// Marketing / Pricing section
echo '<tr valign="top"><th colspan="2"><h2>Marketing (Pricing Landing)</h2></th></tr>';

// Checkout URLs
$checkout_starter = esc_url(get_option(self::OPTION_CHECKOUT_URL_STARTER, ''));
$checkout_pro = esc_url(get_option(self::OPTION_CHECKOUT_URL_PRO, ''));
$checkout_agency = esc_url(get_option(self::OPTION_CHECKOUT_URL_AGENCY, ''));

echo '<tr valign="top">';
echo '<th scope="row">Checkout URL Basic</th>';
echo '<td>';
echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_STARTER) . '" value="' . $checkout_starter . '" size="50">';
echo '<br><small>Z.B.: https://gumroad.com/checkout/...</small>';
echo '</td></tr>';

echo '<tr valign="top">';
echo '<th scope="row">Checkout URL Pro</th>';
echo '<td>';
echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_PRO) . '" value="' . $checkout_pro . '" size="50">';
echo '<br><small>Z.B.: https://gumroad.com/checkout/...</small>';
echo '</td></tr>';

echo '<tr valign="top">';
echo '<th scope="row">Checkout URL Studio</th>';
echo '<td>';
echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_AGENCY) . '" value="' . $checkout_agency . '" size="50">';
echo '<br><small>Z.B.: https://gumroad.com/checkout/...</small>';
echo '</td></tr>';
```

---

## Prompt C: Shortcode `[ltl_saas_pricing]`

### Implementation im Portal Plugin

**Datei**: [wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php](../../wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php)

**In `init()` Methode hinzufügen** (nach Dashboard-Shortcode):
```php
add_shortcode( 'ltl_saas_pricing', array( $this, 'shortcode_pricing' ) );
```

**Neue Methode hinzufügen**:
```php
public function shortcode_pricing( $atts = [] ) {
    $lang = isset($atts['lang']) ? $atts['lang'] : 'de';

    // Checkout URLs abrufen
    require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/Admin/class-admin.php';
    $checkout_starter = get_option(LTL_SAAS_Portal_Admin::OPTION_CHECKOUT_URL_STARTER, '');
    $checkout_pro = get_option(LTL_SAAS_Portal_Admin::OPTION_CHECKOUT_URL_PRO, '');
    $checkout_agency = get_option(LTL_SAAS_Portal_Admin::OPTION_CHECKOUT_URL_AGENCY, '');

    ob_start();
    ?>
    <div class="ltl-saas-pricing" style="max-width: 1200px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">

        <!-- Hero -->
        <div style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h1 style="font-size: 2.5em; margin: 0 0 10px 0;">
                <?php echo $lang === 'en' ? 'Automatically Write Blog Posts with AI' : 'Schreibe automatisch Blogposts mit KI'; ?>
            </h1>
            <p style="font-size: 1.2em; margin: 0; opacity: 0.9;">
                <?php echo $lang === 'en' ? 'No tech skills required' : 'Ohne technische Skills'; ?>
            </p>
        </div>

        <!-- Benefits -->
        <div style="padding: 40px 20px; background: #f8f9fa;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="padding: 20px;">
                    <h3>✅ <?php echo $lang === 'en' ? 'Save Time' : 'Zeit sparen'; ?></h3>
                    <p><?php echo $lang === 'en' ? 'Write a post in 5 minutes' : '5 Minuten statt Stunden schreiben'; ?></p>
                </div>
                <div style="padding: 20px;">
                    <h3>✅ <?php echo $lang === 'en' ? 'SEO & Traffic' : 'SEO & Traffic'; ?></h3>
                    <p><?php echo $lang === 'en' ? 'Fresh content boosts ranking' : 'Frische Inhalte booten dein Ranking'; ?></p>
                </div>
                <div style="padding: 20px;">
                    <h3>✅ <?php echo $lang === 'en' ? 'Full Control' : 'Volle Kontrolle'; ?></h3>
                    <p><?php echo $lang === 'en' ? 'Always draft-first' : 'Immer Draft zum Prüfen'; ?></p>
                </div>
                <div style="padding: 20px;">
                    <h3>✅ <?php echo $lang === 'en' ? 'AI Quality' : 'KI-Qualität'; ?></h3>
                    <p><?php echo $lang === 'en' ? 'Customizable tones' : 'Personalisierbare Töne'; ?></p>
                </div>
            </div>
        </div>

        <!-- Plan Cards -->
        <div style="padding: 40px 20px;">
            <h2 style="text-align: center; margin-bottom: 30px;">
                <?php echo $lang === 'en' ? 'Choose Your Plan' : 'Wähle deinen Plan'; ?>
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">

                <!-- Basic -->
                <div style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 20px; text-align: center;">
                    <h3>Basic</h3>
                    <p style="font-size: 2em; color: #667eea; margin: 20px 0;">
                        <strong>€19</strong><span style="font-size: 0.6em; color: #666;">/<?php echo $lang === 'en' ? 'month' : 'Mo'; ?></span>
                    </p>
                    <ul style="text-align: left; margin: 20px 0; list-style: none; padding: 0;">
                        <li>✓ 30 <?php echo $lang === 'en' ? 'posts/month' : 'Posts/Monat'; ?></li>
                        <li>✓ 1 RSS <?php echo $lang === 'en' ? 'source' : 'Quelle'; ?></li>
                        <li>✓ 6 <?php echo $lang === 'en' ? 'languages' : 'Sprachen'; ?></li>
                        <li>✓ Standard AI</li>
                    </ul>
                    <?php if ($checkout_starter): ?>
                        <a href="<?php echo esc_url($checkout_starter); ?>" class="button" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
                            <?php echo $lang === 'en' ? 'Get Started' : 'Starten'; ?>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Pro -->
                <div style="border: 2px solid #667eea; border-radius: 8px; padding: 20px; text-align: center; background: #f0f4ff; transform: scale(1.05);">
                    <div style="background: #667eea; color: white; padding: 5px; border-radius: 4px; margin-bottom: 10px; font-size: 0.9em; font-weight: bold;">
                        <?php echo $lang === 'en' ? 'POPULAR' : 'BELIEBT'; ?>
                    </div>
                    <h3>Pro</h3>
                    <p style="font-size: 2em; color: #667eea; margin: 20px 0;">
                        <strong>€49</strong><span style="font-size: 0.6em; color: #666;">/<?php echo $lang === 'en' ? 'month' : 'Mo'; ?></span>
                    </p>
                    <ul style="text-align: left; margin: 20px 0; list-style: none; padding: 0;">
                        <li>✓ 120 <?php echo $lang === 'en' ? 'posts/month' : 'Posts/Monat'; ?></li>
                        <li>✓ 3 RSS <?php echo $lang === 'en' ? 'sources' : 'Quellen'; ?></li>
                        <li>✓ 12 <?php echo $lang === 'en' ? 'languages' : 'Sprachen'; ?></li>
                        <li>✓ Premium AI</li>
                        <li>✓ Email Support</li>
                    </ul>
                    <?php if ($checkout_pro): ?>
                        <a href="<?php echo esc_url($checkout_pro); ?>" class="button" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
                            <?php echo $lang === 'en' ? 'Get Started' : 'Starten'; ?>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Studio -->
                <div style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 20px; text-align: center;">
                    <h3>Studio</h3>
                    <p style="font-size: 2em; color: #667eea; margin: 20px 0;">
                        <strong><?php echo $lang === 'en' ? 'Custom' : 'Individuell'; ?></strong>
                    </p>
                    <ul style="text-align: left; margin: 20px 0; list-style: none; padding: 0;">
                        <li>✓ 300 <?php echo $lang === 'en' ? 'posts/month' : 'Posts/Monat'; ?></li>
                        <li>✓ Unlimited RSS</li>
                        <li>✓ <?php echo $lang === 'en' ? 'All' : 'Alle'; ?> Languages</li>
                        <li>✓ Custom AI</li>
                        <li>✓ Phone + Slack Support</li>
                    </ul>
                    <a href="mailto:contact@lazytechlab.de" class="button" style="background: #666; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
                        <?php echo $lang === 'en' ? 'Contact Us' : 'Kontakt'; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div style="text-align: center; padding: 40px 20px; background: #f8f9fa;">
            <h2><?php echo $lang === 'en' ? 'Ready to get started?' : 'Bereit zum Starten?'; ?></h2>
            <p style="font-size: 1.1em;">
                <?php echo $lang === 'en' ? 'No credit card required. Just WordPress + RSS URL.' : 'Keine Kreditkarte erforderlich. Nur WordPress + RSS-URL.'; ?>
            </p>
            <?php if ($checkout_starter): ?>
                <a href="<?php echo esc_url($checkout_starter); ?>" class="button" style="background: #667eea; color: white; padding: 15px 40px; text-decoration: none; border-radius: 4px; display: inline-block; font-size: 1.1em;">
                    <?php echo $lang === 'en' ? 'Try for Free' : 'Kostenlos testen'; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .ltl-saas-pricing .button {
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .ltl-saas-pricing .button:hover {
            opacity: 0.8;
        }
        @media (max-width: 768px) {
            .ltl-saas-pricing h1 {
                font-size: 1.8em !important;
            }
            .ltl-saas-pricing [style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}
```

---

## Prompt D: Publish Anleitung

Erstelle `docs/marketing/PUBLISH_LANDING.md`:

### Schritt 1: WordPress Seite erstellen
1. WP Admin: **Seiten → Neue Seite hinzufügen**
2. Titel: `Preise` oder `Pricing` (Englisch: `Pricing`)
3. Slug: `/preise` oder `/pricing`
4. Sichtbarkeit: **Öffentlich**

### Schritt 2: Shortcode einfügen
1. Im Seiten-Editor: Klick **+ Neuer Block**
2. Wähle **Shortcode**
3. Tippe ein: `[ltl_saas_pricing]` oder `[ltl_saas_pricing lang="en"]`
4. Speichern

Alternativ direkt in HTML-Editor:
```html
<!-- wp:shortcode -->
[ltl_saas_pricing]
<!-- /wp:shortcode -->
```

### Schritt 3: Checkout Links setzen
1. WP Admin: **LTL AutoBlog Cloud → Marketing (Pricing Landing)**
2. Trage die Checkout-URLs ein:
    - **Checkout URL Basic**: (Dein Gumroad Basic Link; gespeichert im "Starter" Feld)
   - **Checkout URL Pro**: (Deine Gumroad Pro Link)
    - **Checkout URL Studio**: (z.B. mailto:contact@...; gespeichert im "Agency" Feld)
3. Speichern

### Schritt 4: Testen
1. **Desktop**: Öffne `/preise` im Browser
   - Seite lädt ohne Fehler ✓
   - Buttons zeigen auf Checkout URLs ✓
   - Planvergleich sichtbar ✓

2. **Mobile**: Öffne auf Smartphone
   - Responsive Layout OK ✓
   - Buttons klickbar ✓

3. **Incognito**: Öffne ohne Login
   - Seite sichtbar ✓
   - Buttons funktionieren ✓

---

## Prompt E: Smoke Tests

Erstelle `docs/testing/smoke/issue-19.md`:

### Test 1: Landing Seite lädt ohne Login

```bash
# Command
curl -i https://yourdomain/preise
```

**Erwartet**:
- HTTP 200 OK ✓
- HTML enthält "Preise" / "Pricing" ✓
- Keine PHP Errors ✓

---

### Test 2: Shortcode Rendering

**Setup**: Logge dich aus (Incognito-Modus)

**Test**:
1. Öffne `/preise`
2. Prüfe:
   - ✓ Hero Section sichtbar
   - ✓ Benefits (5 Punkte) sichtbar
    - ✓ Plan Cards (Free/Basic/Pro/Studio) sichtbar
   - ✓ CTA Button sichtbar

**Kein Fehler**:
- ✓ Keine PHP Notices in `wp-content/debug.log`
- ✓ Seite lädt schnell (< 1s)

---

### Test 3: Checkout Buttons zeigen auf richtige URLs

**Setup**:
1. Admin: Setze Checkout URLs:
    - Basic: https://gumroad.com/checkout/basic
   - Pro: https://gumroad.com/checkout/pro
    - Studio: mailto:contact@...

**Test**:
1. Öffne `/preise` (ohne Login)
2. Klick auf Free CTA → WP Registrierung/Login ✓
3. Klick auf "Get Started" Basic Button
4. Browser navigiert zu: `https://gumroad.com/checkout/basic` ✓
4. Klick auf Pro Button → `https://gumroad.com/checkout/pro` ✓

**Verifizierung**:
```bash
# Browser DevTools → Network Tab
# Klick Button → Prüfe Redirect URL
```

---

### Test 4: Responsive Design (Mobile)

**Setup**: Nutze Chrome DevTools (F12) → Toggle Device Toolbar

**Test**:
1. iPhone 12 (390x844):
   - ✓ Plan Cards stacked vertikal
   - ✓ Text lesbar
   - ✓ Buttons klickbar

2. iPad (768x1024):
   - ✓ 2-Spalten Layout
   - ✓ Alle Inhalte sichtbar

**Kein Layout Bruch** ✓

---

### Test 5: Sprachen-Parameter

**Setup**:
1. Shortcode mit `lang="en"`:
   - `[ltl_saas_pricing lang="en"]`

**Test**:
1. Erstelle neue Test-Seite mit `[ltl_saas_pricing lang="en"]`
2. Öffne Seite
3. Prüfe:
   - ✓ Headlines auf Englisch
   - ✓ Plan Details auf Englisch
   - ✓ Buttons mit englischem Text

---

### Test 6: Fehlerbehandlung (Fehlende Checkout URLs)

**Setup**:
1. Lösche alle Checkout URLs im Admin
2. Öffne `/preise`

**Erwartet**:
- ✓ Seite lädt trotzdem
- ✓ Buttons zeigen trotzdem (mit mailto oder ohne Link)
- ✓ Kein PHP Error
- ✓ Kein blank-white-screen

---

## Checklist für PR

- [ ] Landing Copy (DE/EN) in `docs/marketing/LANDING_PAGE_DE_EN.md` ✓
- [ ] Admin Settings (Checkout URLs) in `class-admin.php` ✓
- [ ] Shortcode `[ltl_saas_pricing]` in `class-ltl-saas-portal.php` ✓
- [ ] Publishing Anleitung in `docs/marketing/PUBLISH_LANDING.md` ✓
- [ ] Smoke Tests in `docs/testing/smoke/issue-19.md` ✓
- [ ] Alle Tests grün ✓

---

## Commit Command

```bash
git add -A
git commit -m "Issue #19: Pricing Landing Page (Copy + Shortcode + Admin Settings)

- Copy in DE/EN (docs/marketing/LANDING_PAGE_DE_EN.md)
- Admin Settings: Checkout URLs für 3 Plans
- Shortcode [ltl_saas_pricing] (public, responsive)
- Publishing guide + smoke tests

Closes #19"
```

---

## PR Beschreibung

```markdown
## Issue #19: Pricing Landing Page

### Summary
Complete pricing landing page with:
- Clean design (Hero + Benefits + Plans + CTA)
- Responsive (Desktop/Tablet/Mobile)
- Bilingual (DE/EN)
- Public (no login required)

### Changes
- Landing page copy (DE/EN)
- Admin settings for checkout URLs
- Shortcode `[ltl_saas_pricing lang="de|en"]`
- Publishing guide
- 6 Smoke Tests

### Testing
All tests pass. Tested on desktop, tablet, mobile.

### Closes
Closes #19
```
