# LTL AutoBlog Cloud Portal - Translations

## Available Languages

- **English (en_US)** - Default language
- **German (de_DE)** - Full translation included

## How Translations Work

The plugin uses WordPress's built-in translation system (i18n).

### File Structure

```
languages/
├── ltl-saas-portal.pot          # Template file (all translatable strings)
├── ltl-saas-portal-de_DE.po     # German translation (human-readable)
└── ltl-saas-portal-de_DE.mo     # German translation (binary, loaded by WordPress)
```

### Files Explained

- **`.pot`** - Portable Object Template - Contains all English strings that can be translated
- **`.po`** - Portable Object - Human-readable translation file
- **`.mo`** - Machine Object - Binary compiled version used by WordPress

## How to Add/Update Translations

### Option 1: Using Poedit (Recommended)

1. Download [Poedit](https://poedit.net/) (free)
2. Open `languages/ltl-saas-portal-de_DE.po`
3. Edit translations
4. Save (automatically compiles to `.mo`)

### Option 2: Manual Editing

1. Edit `languages/ltl-saas-portal-de_DE.po` in any text editor
2. Find the `msgid` (English) and edit the `msgstr` (German):
   ```
   msgid "Save Changes"
   msgstr "Änderungen speichern"
   ```
3. Compile to `.mo` using:
   ```bash
   php compile-po-to-mo.php
   ```

### Option 3: Using gettext (Advanced)

If you have `gettext` installed:
```bash
msgfmt -o languages/ltl-saas-portal-de_DE.mo languages/ltl-saas-portal-de_DE.po
```

## How to Add a New Language

### Example: Adding French (fr_FR)

1. **Copy the German PO file:**
   ```bash
   cp languages/ltl-saas-portal-de_DE.po languages/ltl-saas-portal-fr_FR.po
   ```

2. **Edit the header:**
   ```
   "Language: fr_FR\n"
   "Language-Team: French\n"
   ```

3. **Translate all `msgstr` entries:**
   ```
   msgid "Save Changes"
   msgstr "Enregistrer les modifications"
   ```

4. **Compile to MO:**
   ```bash
   php compile-po-to-mo.php
   ```

   Or manually:
   ```bash
   msgfmt -o languages/ltl-saas-portal-fr_FR.mo languages/ltl-saas-portal-fr_FR.po
   ```

5. **WordPress will automatically load the correct translation** based on the site language setting.

## How WordPress Selects Language

WordPress automatically loads the correct translation based on:

1. **Site Language** (Settings → General → Site Language)
2. **User Language** (Users → Your Profile → Language)

### Change Site Language in WordPress

1. Go to **Settings → General**
2. Find **Site Language**
3. Select **Deutsch** for German or **English** for English
4. Click **Save Changes**

The plugin will automatically switch languages!

## Testing Translations

### Test German Translation

1. Go to WordPress Admin → **Settings → General**
2. Set **Site Language** to **Deutsch**
3. Go to **LTL AutoBlog Cloud Portal** settings
4. All text should now be in German! 🇩🇪

### Test English (Default)

1. Set **Site Language** to **English (United States)**
2. All text appears in English 🇺🇸

## For Developers

### Using Translation Functions

All user-facing strings use WordPress i18n functions:

```php
// Simple translation
__( 'Save Changes', 'ltl-saas-portal' )

// Translation with echo
_e( 'Save Changes', 'ltl-saas-portal' )

// Translation with HTML escaping
esc_html__( 'Save Changes', 'ltl-saas-portal' )

// Translation with context
_x( 'Save', 'button label', 'ltl-saas-portal' )
```

### Updating POT File

When adding new translatable strings:

1. Use a tool like [WP-CLI](https://developer.wordpress.org/cli/commands/i18n/make-pot/):
   ```bash
   wp i18n make-pot . languages/ltl-saas-portal.pot
   ```

2. Or use [Poedit](https://poedit.net/):
   - Open existing PO file
   - Catalog → Update from sources
   - Save

### Text Domain

All strings use the text domain: `ltl-saas-portal`

This is defined in the plugin header:
```php
Text Domain: ltl-saas-portal
Domain Path: /languages
```

## Translation Status

### Current Coverage

- ✅ Admin Settings Page
- ✅ Design/Customizer Page
- ✅ REST API Error Messages
- ✅ Preview Elements
- ✅ Status Messages

### Language Support

| Language | Code | Status | Translator |
|----------|------|--------|------------|
| English | en_US | ✅ Default | LazyTechLab |
| German | de_DE | ✅ Complete | LazyTechLab |
| French | fr_FR | ❌ Not started | - |
| Spanish | es_ES | ❌ Not started | - |

## Need Help?

- [WordPress I18n Handbook](https://developer.wordpress.org/apis/handbook/internationalization/)
- [Poedit Documentation](https://poedit.net/trac/wiki/Doc)
- [GNU gettext Manual](https://www.gnu.org/software/gettext/manual/)

## Contributing Translations

Want to help translate?

1. Fork the repository
2. Add your language PO/MO files
3. Submit a pull request

We appreciate all contributions! 🌍
