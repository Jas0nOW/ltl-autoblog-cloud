# Issue #20: Onboarding Wizard — Dashboard UI + Tests

## Prompt B: Setup-Progress Block (Dashboard Enhancement)

### Wo: Dashboard Shortcode

**Datei**: [wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php](../../wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal.php)

**In `shortcode_dashboard()` Methode**:

Vor dem Hauptformular einen Progress-Block hinzufügen:

```html
<div style="background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h2>📋 Dein Setup-Fortschritt</h2>

    <!-- Step 1: WordPress Connection -->
    <div style="display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
        <div style="font-size: 1.5em; margin-right: 15px;">
            [<?php echo $connection_exists ? '✅' : '⚠️'; ?>]
        </div>
        <div>
            <strong>Schritt 1: WordPress verbinden</strong>
            <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                <?php echo $connection_exists ? 'Verbunden ✓' : 'Noch nicht konfiguriert'; ?>
            </p>
        </div>
        <div style="margin-left: auto;">
            <a href="#wp-connection" class="button <?php echo $connection_exists ? 'button-secondary' : 'button-primary'; ?>">
                <?php echo $connection_exists ? 'Bearbeiten' : 'Jetzt verbinden'; ?>
            </a>
        </div>
    </div>

    <!-- Step 2: RSS + Settings -->
    <div style="display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
        <div style="font-size: 1.5em; margin-right: 15px;">
            [<?php echo $settings_complete ? '✅' : '⚠️'; ?>]
        </div>
        <div>
            <strong>Schritt 2: RSS-Feed + Einstellungen</strong>
            <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                <?php echo $settings_complete ? 'RSS: ' . esc_html(substr($settings['rss_url'], 0, 30)) . '...' : 'Noch nicht konfiguriert'; ?>
            </p>
        </div>
        <div style="margin-left: auto;">
            <a href="#settings" class="button <?php echo $settings_complete ? 'button-secondary' : 'button-primary'; ?>">
                <?php echo $settings_complete ? 'Bearbeiten' : 'Jetzt konfigurieren'; ?>
            </a>
        </div>
    </div>

    <!-- Step 3: Plan Aktiv -->
    <div style="display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
        <div style="font-size: 1.5em; margin-right: 15px;">
            [<?php echo $is_active ? '✅' : '❌'; ?>]
        </div>
        <div>
            <strong>Schritt 3: Plan aktiv</strong>
            <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                <?php echo $is_active ? 'Plan: ' . esc_html($plan) . ' ✓' : '⚠️ Plan nicht aktiv (kein Gumroad-Abo?)'; ?>
            </p>
        </div>
        <div style="margin-left: auto;">
            <?php if (!$is_active): ?>
                <a href="https://gumroad.com/..." class="button button-primary">Plan kaufen</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Step 4: First Run -->
    <div style="display: flex; align-items: center; padding: 10px 0;">
        <div style="font-size: 1.5em; margin-right: 15px;">
            [<?php echo $first_run_success ? '✅' : ($last_run_attempted ? '⚠️' : '⏳'); ?>]
        </div>
        <div>
            <strong>Schritt 4: Erster Post veröffentlicht</strong>
            <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                <?php echo $first_run_success ? 'Letzer Run: erfolgreich (vor ' . $last_run_ago . ')' : 'Warte auf ersten Run oder versuche manuell'; ?>
            </p>
        </div>
        <div style="margin-left: auto;">
            <?php if (!$first_run_success && $connection_exists && $settings_complete): ?>
                <a href="#test-run" class="button button-primary">🚀 Test Run</a>
            <?php endif; ?>
        </div>
    </div>
</div>
```

### Logik für Progress-Block

```php
// In shortcode_dashboard()
$connection_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$conn_table} WHERE user_id = %d",
    $user_id
));

$settings_complete = false;
if ($settings_row && !empty($settings_row->rss_url)) {
    $settings_complete = true;
}

$is_active = isset($settings_row->is_active) && (bool)$settings_row->is_active;
$plan = isset($settings_row->plan) ? $settings_row->plan : 'free';

// Last run status
$last_run = $wpdb->get_row($wpdb->prepare(
    "SELECT status, created_at FROM {$runs_table} WHERE tenant_id = %d ORDER BY created_at DESC LIMIT 1",
    $user_id
));
$first_run_success = $last_run && $last_run->status === 'success';
$last_run_attempted = (bool)$last_run;
```

---

## Prompt C: Hint Texts + Inline Validation

### Wo: Form Fields im Dashboard

**Beispiel 1: RSS URL**
```html
<label for="rss_url">
    📰 RSS-Quelle
    <span title="Help" style="cursor: help; color: #667eea;">ℹ️</span>
</label>
<input type="url" id="rss_url" name="rss_url" placeholder="https://beispiel.de/feed" value="<?php echo esc_attr($rss_url); ?>">
<small style="color: #666;">
    ✓ Muss https:// sein |
    💡 Beispiele: blog.de/feed, news-portal.com/rss |
    <a href="#" onclick="testRss(); return false;">Test RSS</a>
</small>
<div id="rss-feedback" style="margin-top: 5px;"></div>
```

**Beispiel 2: WordPress URL**
```html
<label for="wp_url">
    🔗 WordPress URL
    <span title="Deine Website Domain"  style="cursor: help; color: #667eea;">ℹ️</span>
</label>
<input type="url" id="wp_url" name="wp_url" placeholder="https://meinblog.de" value="<?php echo esc_attr($wp_url); ?>">
<small style="color: #666;">
    ✓ Muss https:// sein |
    💡 Beispiel: https://meinseite.de (ohne /wp-admin) |
    <a href="#" onclick="testConnection(); return false;">🧪 Verbindung testen</a>
</small>
<div id="connection-feedback" style="margin-top: 5px;"></div>
```

**Beispiel 3: Frequenz**
```html
<label for="frequency">📅 Frequenz</label>
<select id="frequency" name="frequency">
    <option value="daily" <?php selected($frequency, 'daily'); ?>>Täglich</option>
    <option value="3x_week" <?php selected($frequency, '3x_week'); ?>>3x pro Woche</option>
    <option value="weekly" <?php selected($frequency, 'weekly'); ?>>Wöchentlich</option>
</select>
<small style="color: #666;">
    💡 Auswahl ändert sich ab dem nächsten Cycle.
    📊 Bei Starter-Plan: max 80 Posts/Monat.
</small>
```

### JavaScript für Inline Validation

```javascript
function testConnection() {
    const wpUrl = document.getElementById('wp_url').value;
    const wpUser = document.getElementById('wp_user').value;
    const wpPass = document.getElementById('wp_app_password').value;

    if (!wpUrl || !wpUser || !wpPass) {
        showFeedback('connection-feedback', '❌ Alle Felder erforderlich', 'error');
        return;
    }

    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=ltl_saas_test_connection', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ wp_url: wpUrl, wp_user: wpUser, wp_app_password: wpPass })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showFeedback('connection-feedback', '✅ Verbindung erfolgreich!', 'success');
        } else {
            showFeedback('connection-feedback', '❌ Fehler: ' + data.message, 'error');
        }
    })
    .catch(err => showFeedback('connection-feedback', '❌ Netzwerkfehler', 'error'));
}

function testRss() {
    const rssUrl = document.getElementById('rss_url').value;

    if (!rssUrl) {
        showFeedback('rss-feedback', '❌ RSS-URL erforderlich', 'error');
        return;
    }

    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=ltl_saas_test_rss', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ rss_url: rssUrl })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showFeedback('rss-feedback', '✅ RSS OK! Titel: ' + data.title, 'success');
        } else {
            showFeedback('rss-feedback', '❌ Fehler: ' + data.message, 'error');
        }
    })
    .catch(err => showFeedback('rss-feedback', '❌ Netzwerkfehler', 'error'));
}

function showFeedback(elementId, message, type) {
    const el = document.getElementById(elementId);
    el.textContent = message;
    el.style.color = type === 'success' ? '#28a745' : '#dc3545';
    el.style.fontWeight = 'bold';
}
```

---

## Prompt D: Test Buttons (Test Connection + Test RSS)

### REST Endpoints für Tests

**Datei**: [wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php](../../wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php)

**In `register_routes()` hinzufügen**:
```php
register_rest_route( self::NAMESPACE, '/test-connection', array(
    'methods'  => 'POST',
    'callback' => array( $this, 'test_wp_connection_quick' ),
    'permission_callback' => function() { return is_user_logged_in(); },
) );

register_rest_route( self::NAMESPACE, '/test-rss', array(
    'methods'  => 'POST',
    'callback' => array( $this, 'test_rss_quick' ),
    'permission_callback' => function() { return is_user_logged_in(); },
) );
```

**Neue Methoden**:
```php
public function test_wp_connection_quick( $request ) {
    $params = $request->get_json_params();
    $wp_url = isset($params['wp_url']) ? esc_url_raw($params['wp_url']) : '';
    $wp_user = isset($params['wp_user']) ? sanitize_user($params['wp_user']) : '';
    $wp_pass = isset($params['wp_app_password']) ? $params['wp_app_password'] : '';

    if (!$wp_url || !$wp_user || !$wp_pass) {
        return new WP_REST_Response(['success' => false, 'message' => 'Missing fields'], 400);
    }

    $api_url = rtrim($wp_url, '/') . '/wp-json/wp/v2/users/me';
    $auth = base64_encode($wp_user . ':' . $wp_pass);
    $resp = wp_remote_get($api_url, [
        'headers' => ['Authorization' => 'Basic ' . $auth],
        'timeout' => 5,
    ]);

    $code = wp_remote_retrieve_response_code($resp);
    if ($code === 200) {
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        return ['success' => true, 'user' => $body['name'] ?? 'OK'];
    } else {
        return ['success' => false, 'message' => 'HTTP ' . $code];
    }
}

public function test_rss_quick( $request ) {
    $params = $request->get_json_params();
    $rss_url = isset($params['rss_url']) ? esc_url_raw($params['rss_url']) : '';

    if (!$rss_url) {
        return new WP_REST_Response(['success' => false, 'message' => 'Missing RSS URL'], 400);
    }

    $resp = wp_remote_get($rss_url, ['timeout' => 5]);
    $code = wp_remote_retrieve_response_code($resp);

    if ($code !== 200) {
        return ['success' => false, 'message' => 'HTTP ' . $code];
    }

    $body = wp_remote_retrieve_body($resp);
    $xml = simplexml_load_string($body);

    if ($xml === false) {
        return ['success' => false, 'message' => 'Invalid XML/RSS'];
    }

    // Try to get first item title
    $title = '';
    if (isset($xml->channel->item[0]->title)) {
        $title = (string)$xml->channel->item[0]->title;
    } elseif (isset($xml->entry[0]->title)) {
        $title = (string)$xml->entry[0]->title;
    }

    return ['success' => true, 'title' => substr($title, 0, 50)];
}
```

---

## Prompt E: Smoke Tests Issue #20

Erstelle `docs/SMOKE_TEST_ISSUE_20.md`:

### Test 1: Fresh User ohne Settings → Alle Steps offen

**Setup**: Neuer User, gerade registriert

**Test**:
1. Öffne Dashboard
2. Prüfe Progress Block:
   - Step 1: ⚠️ (grau, "Noch nicht konfiguriert")
   - Step 2: ⚠️ (grau)
   - Step 3: ❌ (rot, "Plan nicht aktiv")
   - Step 4: ⏳ (grau, "Warte auf...") oder ⚠️

**Expected**:
- Alle Buttons: Primär-farbe (blue)
- Keine Fehler im Console
- Progress sichtbar und klar

---

### Test 2: Nach WordPress Connect → Step 1 ✅

**Setup**: User gibt WP-URL + User + Password ein

**Test**:
1. Klick "Test Connection"
   - Sollte: "✅ Verbindung erfolgreich!"
2. Klick "Speichern"
3. Page reload
4. Prüfe Progress:
   - Step 1: ✅ (grün, Haken)

**Expected**:
- Step 1 grün
- Übrige Steps weiterhin offen

---

### Test 3: Nach RSS Setup → Step 2 ✅

**Setup**: User gibt RSS-URL + Einstellungen ein

**Test**:
1. Klick "Test RSS"
   - Sollte: "✅ RSS OK! Titel: ..."
2. Klick "Speichern"
3. Page reload
4. Prüfe Progress:
   - Step 2: ✅ (grün)

**Expected**:
- Step 1 & 2 grün
- Step 3 noch ⚠️/❌

---

### Test 4: wenn is_active=0 → Step 3 zeigt "Upgrade"

**Setup**: User Plan deaktiviert (is_active=0)

**Test**:
1. Öffne Dashboard
2. Prüfe Progress Step 3:
   - ❌ (rot)
   - Text: "Plan nicht aktiv"
   - Button: "Plan kaufen"

**Expected**:
- CTA deutlich sichtbar
- Kein Fehler

---

### Test 5: Nach erfolgreichem Run → Step 4 ✅

**Setup**: User führt Test Run durch (oder Make macht echten Run)

**Test**:
1. Klick "Test Run" (falls Button sichtbar)
2. Warte 30-60 Sekunden
3. Page reload
4. Prüfe Progress Step 4:
   - ✅ (grün)
   - Text: "Letzer Run: erfolgreich (vor XY)"

**Expected**:
- Alle 4 Steps grün (wenn auch Plan aktiv)
- Time-stamp korrekt

---

### Test 6: Fehlerbehandlung (ungültige RSS)

**Setup**: RSS-URL existiert nicht

**Test**:
1. Gib falsche RSS-URL ein: `https://invalid.xyz/feed`
2. Klick "Test RSS"
3. Sollte: "❌ Fehler: HTTP 404" oder ähnlich

**Expected**:
- Fehler-Feedback angezeigt
- Kein Crash
- Form bleibt editable

---

### Test 7: Hint-Texte angezeigt

**Setup**: Dashboard offen

**Test**:
1. Prüfe RSS-URL Feld:
   - Sollte small-Text haben: "Muss https:// sein | Beispiele: ..."
   - Link "Test RSS" sichtbar
2. Prüfe WordPress-URL Feld:
   - Sollte small-Text: "Muss https:// sein | Beispiel: ..."
   - Link "Verbindung testen" sichtbar

**Expected**:
- Alle Hints sichtbar (nicht versteckt)
- Links funktionieren

---

### Smoke Test Checklist

- [ ] Test 1: Fresh User → Alle Steps offen ✓
- [ ] Test 2: WP Connect → Step 1 ✅ ✓
- [ ] Test 3: RSS Setup → Step 2 ✅ ✓
- [ ] Test 4: No Plan → Step 3 zeigt Upgrade ✓
- [ ] Test 5: After Run → Step 4 ✅ ✓
- [ ] Test 6: Error Handling (RSS) ✓
- [ ] Test 7: Hint-Texts sichtbar ✓

---

## Commit & PR Issue #20

```bash
git add -A
git commit -m "Issue #20: Onboarding Wizard (Progress Block + Hints + Tests)

- Detaillierte Onboarding Anleitung (docs/ONBOARDING_DETAILED.md)
- Dashboard Progress Block (Setup-Schritte mit Status)
- Hint-Texte für alle Form-Felder
- Test Buttons: Test Connection + Test RSS (REST Endpoints)
- 7 Smoke Tests für Onboarding-Flow

Closes #20"
```

---

## All Done!

✅ Issue #17: Retry-Strategie (4 Prompts)
✅ Issue #19: Pricing Landing (5 Prompts)
✅ Issue #20: Onboarding Wizard (5 Prompts)

**Total: 14 Prompts implementiert!**
