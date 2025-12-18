# TESTING_LOG_TEMPLATE.md

> Kopiere diese Datei nach `docs/TESTING_LOG.md` und fülle sie aus.

## Datum
- YYYY-MM-DD

## Umgebung
- WordPress: (Version)
- PHP: (Version)
- SSL: ja/nein
- Host: LocalWP/Hostinger/…

## Sprint 04 Tests — Limits

### Limits — make/tenants
- Test 1: posts_this_month=0 → Erwartet: skip=false → Ergebnis: ___
- Test 2: posts_this_month=limit → Erwartet: skip=true → Ergebnis: ___
- Test 3: posts_period_start=letzter Monat → Erwartet: reset → Ergebnis: ___

### Callback
- Test 4: successful publish → Erwartet: +1 → Ergebnis: ___
- Test 5: month rollover → Erwartet: reset +1 → Ergebnis: ___

### Regression
- Test 6: /active-users maskiert passwords → Ergebnis: ___
- Test 7: /make/tenants ohne Token 403 → Ergebnis: ___

## Notes / Bugs
- …
