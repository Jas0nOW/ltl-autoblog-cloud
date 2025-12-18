# wp-config.php Reverse Proxy SSL Snippet (Dokumentation)

> Nur verwenden, wenn dein Reverse Proxy `X-Forwarded-Proto: https` korrekt setzt.

```php
// Force WordPress to detect HTTPS behind a reverse proxy
if ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false ) {
    $_SERVER['HTTPS'] = 'on';
}
```
