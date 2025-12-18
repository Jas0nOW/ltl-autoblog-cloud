# Onboarding Guide für LTL AutoBlog Cloud (Issue #20)

> **Ziel**: Neukunde ohne Support durch Setup bringen.

---

## Schritt 1: Account erstellen & Anmelden

### 1.1 Zahlungsweg wählen

**Du hast 3 Möglichkeiten:**

#### Option A: Free (ohne Zahlung)
1. Öffne https://lazytechlab.de
2. Klick auf "Kostenlos starten" (10 Posts/Monat)
3. Registriere dich mit E-Mail + Passwort
4. Fertig – Du kannst sofort loslegen!

#### Option B: Stripe (Empfohlen – auf unserer Website)
1. Öffne https://lazytechlab.de/preise
2. Wähle deinen Plan (Basic/Pro/Studio)
3. Checkout über **Stripe** (Kreditkarte, SEPA, etc.)
4. Nach Zahlung: Account wird automatisch aktiviert
5. Du erhältst eine E-Mail mit Login-Daten

#### Option C: Gumroad (Alternative)
1. Öffne den Gumroad-Link (falls bereitgestellt)
2. Kaufe über Gumroad
3. Account wird automatisch per Webhook erstellt
4. Du erhältst eine E-Mail mit Login-Daten

### 1.2 Account aktivieren & Einloggen

**Nach erfolgreicher Zahlung (Stripe oder Gumroad) oder Registrierung (Free):**

1. Du erhältst eine E-Mail mit:
   - Login-URL: `https://portal.lazytechlab.de/wp-login.php`
   - Benutzername (meist deine E-Mail-Adresse)
   - Temporäres Passwort (bei Stripe/Gumroad)

2. Logge dich ein mit den Zugangsdaten aus der E-Mail

3. (Optional) Ändere dein Passwort: **Profil → Passwort ändern**

✅ **Fertig**: Du bist jetzt eingeloggt!

---

## Schritt 2: WordPress verbinden

### 2.1 Deine WordPress-Adresse herrausfinden
- Beispiele:
  - `https://meinblog.de`
  - `https://blog.mysite.com`
  - `https://example.wordpress.com`

**Wichtig**: Muss HTTPS sein (nicht HTTP)!

### 2.2 Application Password generieren
1. Öffne DEINE WordPress Admin: `https://meinblog.de/wp-admin`
2. Gehe zu: **Nutzer → Dein Profil**
3. Scrolle zu: **Anwendungspasswörter**
4. Gib ein Name ein: `LTL AutoBlog` (oder beliebig)
5. Klick: **Anwendungspasswort erstellen**
6. Kopiere das generierte Passwort (lange Zeichenkette)

### 2.3 Im Portal: WordPress verbinden
1. Logge dich ins LTL Portal ein
2. Klick auf Dashboard
3. Im Setup-Block: "Schritt 1: WordPress verbinden"
4. Gib ein:
   - **WordPress URL**: `https://meinblog.de`
   - **Benutzer**: Dein WP-Username (z.B. `admin`)
   - **App Password**: Das lange Passwort von oben
5. Klick: **„Test Connection"** (prüft, ob alles OK ist)
6. Wenn Test erfolgreich: **„Speichern"**

✅ **Fertig**: WordPress ist verbunden!

---

## Schritt 3: RSS-Feed einrichten

### 3.1 RSS-Feed URL finden

**Wo finde ich eine RSS-URL?**

- **Dein eigener Blog**:
  - WordPress: `https://meinblog.de/feed`
  - Blogspot: `https://myblog.blogspot.com/feeds/posts/default`
- **News-Portale**:
  - BBC: `https://feeds.bbc.co.uk/news/rss.xml`
  - CNN: `https://feeds.cnbc.com/`
- **Competitors/Industry**:
  - Suche im Google: `site:competitor.de inurl:feed`

**Tests die RSS-URL** (optional):
```bash
curl https://example.com/feed | head -20
# Sollte XML mit <rss> oder <feed> zeigen
```

### 3.2 Im Portal: RSS-Feed speichern
1. Im Dashboard: Klick **Schritt 2: Einstellungen**
2. Gib ein:
   - **RSS-URL**: `https://quelle.de/feed`
   - **Sprache**: Deutsch, Englisch, etc.
   - **Ton**: Professionell, Casual, Nerdy, etc.
   - **Draft/Publish**: Normalerweise "Draft" (zum Prüfen)
   - **Frequenz**: Täglich, 3x/Woche, Wöchentlich
3. Klick: **„Test RSS"** (holt Top-News, zeigt Vorschau)
4. Wenn OK: **„Speichern"**

✅ **Fertig**: Settings gespeichert!

---

## Schritt 4: Kostenlos starten (Free Plan)

### 4.1 Test-Run durchführen
1. Im Dashboard: Klick **„Test Run starten"** (wenn vorhanden)
2. Oder warte auf nächste automatische Ausführung (z.B. heute 15:00 Uhr)

### 4.2 Erste Posts überprüfen
1. Öffne deine WordPress Admin: `https://meinblog.de/wp-admin`
2. Gehe zu: **Beiträge**
3. Solltest du neue **Entwürfe** sehen (mit Titel + KI-Text)
4. **Überprüfe den Text**: Sieht es gut aus?
   - Ja → **Veröffentlichen** (oder warte auf Auto-Publish je nach Plan)
   - Nein → Bearbeite oder lösche den Entwurf

✅ **Fertig**: Dein erster Post ist da!

---

## Troubleshooting Top 10

### 1. **"WordPress Connection failed"**
- **Prüfe**: WordPress URL hat https:// ?
- **Prüfe**: Username und App Password korrekt?
- **Prüfe**: Application Password in deiner WP noch aktiv?
- **Lösung**: Generiere neues App Password und versuche erneut

### 2. **"RSS Feed nicht erreichbar"**
- **Prüfe**: RSS-URL korrekt geschrieben?
- **Prüfe**: URL im Browser öffnen (kopiere URL in Tab)
- **Lösung**: Nutze alternative RSS-URL oder überprüfe Quelle

### 3. **"Keine Posts erstellt"**
- **Prüfe**: Plan ist aktiv? (Gumroad Status = zahlend)
- **Prüfe**: WordPress verbunden?
- **Prüfe**: RSS-Feed hat neue Items?
- **Lösung**: Starte manuell „Test Run" oder warte auf nächsten Cycle

### 4. **"Posts sind Müll / nicht relevant"**
- **Tipp**: Ändere RSS-Quelle zu relevanteren Themen
- **Tipp**: Nutze konkretere RSS-Feeds (nicht generisch)
- **Tipp**: Justiere Sprache/Ton nach

### 5. **"Passwort vergessen"**
- Gehe zu: `https://yourdomain/wp-login.php`
- Klick: **„Passwort verloren?"**
- E-Mail bestätige und Passwort neu setzen

### 6. **"Kein Email-Zugang nach Zahlung"**
- Prüfe Spam-Ordner (besonders bei Gmail/Outlook)
- **Stripe**: E-Mail kommt von `noreply@lazytechlab.de`
- **Gumroad**: E-Mail kommt von Gumroad + separate E-Mail von uns
- Nutze "Passwort vergessen" auf der Login-Seite
- Support-Email: `support@lazytechlab.de`

### 7. **"SSL Certificate Error"**
- **Prüfe**: Benutzt du http:// statt https:// ?
- **Lösung**: Immer https:// verwenden!
- Wenn Zertifikat ungültig: Kontaktiere dein Hosting oder Support

### 8. **"Rate Limit (429 Fehler)"**
- Deine WordPress hat temporär zu viele Requests erhalten
- **Lösung**: System macht automatisch Retry nach 2 Sekunden
- Wenn Problem bleibt: Prüfe Firewall/Rate-Limit Plugin

### 9. **"Plan abgelaufen"**
- Abo beendet (Stripe oder Gumroad) → Plan ist nicht mehr aktiv
- **Lösung Stripe**: Erneuere dein Abo auf https://lazytechlab.de/account
- **Lösung Gumroad**: Erneuere dein Abo über deinen Gumroad-Link
- Nach Zahlung: Account wird automatisch reaktiviert

### 10. **"Dashboard zeigt alles als unvollständig"**
- Alle Schritte 1-4 durchführen
- Nach Speichern: Dashboard aktualisiert sich
- **Wenn noch nicht OK**: Logout + Re-login

---

## Test: War erfolgreich?

Du weißt, dass alles funktioniert, wenn:

✅ **Dashboard zeigt alle Schritte grün**:
1. ✓ WordPress verbunden
2. ✓ RSS + Einstellungen gespeichert
3. ✓ Plan aktiv (grünes Häkchen)
4. ✓ Letzer Run erfolgreich (grünes Häkchen + "vor 2 Stunden")

✅ **In deiner WordPress Admin**:
- Neue Beiträge als Entwürfe oder Published
- Titel + Text sieht sinnvoll aus
- Posts haben deine RSS-Quelle als Basis

✅ **Zähler läuft**:
- Dashboard zeigt z.B.: "2/10 Posts diesen Monat" (Free) oder "2/30" (Basic)

---

## Nächste Schritte

### Manuell Posts bearbeiten
1. Öffne deine WP Admin
2. Gehe zu **Beiträge**
3. Klick auf Draft
4. Bearbeite Text/Bilder nach Belieben
5. Veröffentliche

### Weitere RSS-Quellen hinzufügen (Pro Plan+)
1. Im Portal: Gehe zu Einstellungen
2. Müsste dann Multi-Feed Option geben
3. Füge mehrere RSS-Feeds hinzu

### Support Kontakt
- Email: `support@lazytechlab.de`
- Docs: `https://yourdomain/docs`
- Community: (wenn vorhanden)

---

## FAQ für die Seite

**Q: Kann ich Posts manuell bearbeiten?**
A: Ja! Posts werden als Entwürfe erstellt. Du kannst sie vor dem Veröffentlichen bearbeiten oder in Echtzeit ändern.

**Q: Wie lange dauert es, bis der erste Post kommt?**
A: Normalerweise innerhalb von 30 Minuten. Bei Basic täglich, bei Pro/Studio mehrmals täglich.

**Q: Was wenn mein WP-Server schlecht reagiert?**
A: Das System macht automatisches Retry nach 2 Sekunden. Wenn danach noch ein Fehler: Wird geloggt und nächster Versuch im nächsten Cycle.

**Q: Kann ich die Frequenz ändern?**
A: Ja, jederzeit in den Einstellungen. Neue Frequenz gilt ab dem nächsten Cycle.

**Q: Kostet das extra wenn ich kündige?**
A: Nein. Neue Posts werden einfach nicht mehr erstellt. Alte Posts bleiben erhalten.

---

## Abkürzungen / Fachbegriffe

| Begriff | Bedeutung |
|---------|-----------|
| RSS | Really Simple Syndication (Web-Feed Format) |
| App Password | WordPress Anwendungspasswort (sicherer als echtes PW) |
| Entwurf | Draft (Post noch nicht veröffentlicht) |
| Veröffentlichen | Publish (Post geht live) |
| Cycle | Zeitraum zwischen zwei Ausführungen (tägl/wöchentl) |
| Plan | Abo-Stufe (Free/Basic/Pro/Studio) |

