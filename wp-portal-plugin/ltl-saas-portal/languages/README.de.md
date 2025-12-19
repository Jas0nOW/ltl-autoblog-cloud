# LTL AutoBlog Cloud Portal - Mehrsprachigkeit

## 🌍 Verfügbare Sprachen

- **Englisch (en_US)** - Standardsprache
- **Deutsch (de_DE)** - Vollständige Übersetzung enthalten

## ⚡ Schnellstart: Sprache wechseln

### In WordPress auf Deutsch umstellen

1. Gehe zu **Einstellungen → Allgemein**
2. Wähle bei **Sprache der Website** → **Deutsch**
3. Klicke auf **Änderungen speichern**
4. Fertig! Das Plugin ist jetzt auf Deutsch 🇩🇪

### Zurück auf Englisch

1. **Settings → General**
2. **Site Language** → **English (United States)**
3. **Save Changes**

## 📝 Übersetzungen bearbeiten

### Mit Poedit (Empfohlen)

1. Lade [Poedit](https://poedit.net/) herunter (kostenlos)
2. Öffne `languages/ltl-saas-portal-de_DE.po`
3. Bearbeite die Übersetzungen
4. Speichern → kompiliert automatisch zu `.mo`

### Manuell bearbeiten

1. Öffne `languages/ltl-saas-portal-de_DE.po` in einem Texteditor
2. Finde das `msgid` (Englisch) und bearbeite das `msgstr` (Deutsch):
   ```
   msgid "Save Changes"
   msgstr "Änderungen speichern"
   ```
3. Kompiliere zu `.mo`:
   ```bash
   php compile-po-to-mo.php
   ```

## ➕ Neue Sprache hinzufügen

Beispiel: Französisch hinzufügen

1. Kopiere die deutsche PO-Datei:
   ```bash
   copy languages\ltl-saas-portal-de_DE.po languages\ltl-saas-portal-fr_FR.po
   ```

2. Bearbeite den Header:
   ```
   "Language: fr_FR\n"
   "Language-Team: French\n"
   ```

3. Übersetze alle `msgstr` Einträge ins Französische

4. Kompiliere:
   ```bash
   php compile-po-to-mo.php
   ```

5. In WordPress **Sprache der Website** auf Französisch stellen

## 📁 Dateistruktur

```
languages/
├── ltl-saas-portal.pot          # Vorlage (alle übersetzbare Texte)
├── ltl-saas-portal-de_DE.po     # Deutsche Übersetzung (lesbar)
├── ltl-saas-portal-de_DE.mo     # Deutsche Übersetzung (kompiliert)
└── README.md                     # Diese Anleitung
```

## ✅ Was ist übersetzt?

- ✅ Admin-Einstellungsseite
- ✅ Design-Seite (Farbanpassungen)
- ✅ REST API Fehlermeldungen
- ✅ Vorschau-Elemente
- ✅ Status-Meldungen
- ✅ Alle Buttons und Labels

## 🔧 Für Entwickler

### Übersetzungsdateien aktualisieren

Nach Änderungen am Code:

```bash
php compile-po-to-mo.php
```

### Neue Texte hinzufügen

Verwende im PHP-Code:

```php
__( 'Text in English', 'ltl-saas-portal' )           // Übersetzen
_e( 'Text in English', 'ltl-saas-portal' )           // Übersetzen + ausgeben
esc_html__( 'Text in English', 'ltl-saas-portal' )   // Übersetzen + escapen
```

Dann in der PO-Datei übersetzen:

```
msgid "Text in English"
msgstr "Text auf Deutsch"
```

## 💡 Hilfe & Support

Fragen? Schau dir die [vollständige englische README](README.md) an oder:

- [WordPress I18n Handbuch](https://developer.wordpress.org/apis/handbook/internationalization/)
- [Poedit Dokumentation](https://poedit.net/trac/wiki/Doc)

---

**Hinweis:** Die Übersetzungen funktionieren automatisch basierend auf der WordPress-Spracheinstellung. Keine zusätzliche Konfiguration nötig!
