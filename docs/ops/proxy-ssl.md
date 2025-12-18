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