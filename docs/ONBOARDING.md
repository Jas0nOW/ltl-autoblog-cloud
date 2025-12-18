# Onboarding — So verbindet ein Kunde seinen WordPress-Blog

## Ziel
Der Kunde verbindet **seine eigene WordPress‑Seite**, damit dein AutoBlog dort posten kann.

## Schritt 1: WordPress Voraussetzungen
- WordPress läuft (Self‑hosted oder Managed WP)
- HTTPS aktiv
- REST API erreichbar (Standard)

## Schritt 2: Application Password erstellen (WP Admin)
1. WP Admin → **Benutzer** → Profil (oder Benutzer bearbeiten)
2. Bereich „Application Passwords“
3. Name z.B. „LTL AutoBlog Cloud“
4. **Erstellen** → Passwort kopieren (wird nur einmal angezeigt)

## Schritt 3: Im Portal eintragen
Im Portal-Dashboard:
- WordPress URL (z.B. https://meinblog.de)
- WP Username (der User, der posten darf)
- Application Password (von oben)
- Optional: Default Category / Tags

## Schritt 4: Verbindung testen
Button „Test Connection“:
- Portal macht einen Test‑Request (z.B. `GET /wp-json/wp/v2/users/me`)
- Wenn ok → grün
- Wenn Fehler:
  - URL falsch?
  - Username/Password falsch?
  - Security Plugin blockt REST?
  - Basic Auth blockiert? (selten, aber möglich)

## Schritt 5: Inhalt konfigurieren
- RSS Feed URL(s)
- Sprache (DE/EN/…)
- Tonart (locker/seriös/…)
- Modus: Draft oder Publish
- Frequenz (z.B. 1/Tag)

## Ergebnis
Ab jetzt wird der Blog automatisch gefüttert (je nach Plan/Limits).
