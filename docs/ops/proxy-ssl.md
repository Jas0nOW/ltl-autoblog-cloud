# Proxy/SSL Setup

## Problem: is_ssl() hinter Reverse Proxy

WordPress prüft SSL mit `is_ssl()`, das sich auf `$_SERVER['HTTPS']` verlässt. Hinter einem Reverse Proxy (z.B. nginx, Cloudflare) ist diese Variable oft nicht gesetzt, obwohl der Client per HTTPS verbunden ist. Das führt zu Problemen bei Weiterleitungen, Cookie-Sicherheit und Mixed Content.

## Lösung: Korrektes Setzen von HTTPS in wp-config.php

Füge in deine `wp-config.php` vor `/* That's all, stop editing! */` ein:

```php
if ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
    $_SERVER['HTTPS'] = 'on';
}
```

Dadurch erkennt WordPress die Verbindung als SSL, wenn der Proxy das Header-Feld `X-Forwarded-Proto: https` setzt.

## Security Note

**Achtung:** Setze dies nur, wenn du dem Proxy-Header vertraust (z.B. eigene Infrastruktur). Bei Shared Hosting oder nicht vertrauenswürdigen Proxies kann ein Angreifer diesen Header fälschen und so HTTPS erzwingen.

## Keine Plugin-Änderung nötig

Diese Anpassung erfolgt ausschließlich in der `wp-config.php` und betrifft nicht das Plugin selbst.

---

## Rate Limiting (Issue #23)

Das Portal implementiert **WP Transient-basiertes Rate Limiting** zum Schutz vor Brute-Force-Attacken auf API-Keys und Token.

### Implementierung

- **Endpoints geschützt**: `/run-callback`, `/make/tenants`
- **Trigger**: 10 fehlgeschlagene Auth-Versuche pro IP in 15 Minuten
- **Antwort**: HTTP 429 (Too Many Requests)
- **Speicher**: WP Transient (DB oder Redis, wenn konfiguriert)

### Beispiel: Brute-Force blockiert
```bash
# Attempt 1-10: curl with wrong API key → 401 Unauthorized
# Attempt 11: curl with any key → 429 Too Many Requests

# Counter setzt sich nach 15 Minuten zurück (Transient TTL)
```

### Optional: IP Allowlist via Reverse Proxy

Für zusätzliche Sicherheit auf Infrastructure-Ebene:

**nginx:**
```nginx
location ~ ^/wp-json/ltl-saas/v1/(run-callback|make/tenants) {
    allow 192.168.1.0/24;  # Make.com IP-Bereich
    deny  all;
    try_files $uri $uri/ /index.php$args;
}
```

**Cloudflare Firewall Rules:**
```
(cf.threat_score > 50) OR (ip.geoip.country == "CN")
→ Block
```

### Logging

Wenn Rate Limit aktiv wird:
```
[LTL-SAAS] Rate limit exceeded: IP=203.0.113.42, endpoint=run-callback, attempts=10
```