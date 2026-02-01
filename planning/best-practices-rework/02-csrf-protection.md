# CSRF Protection Implementation Plan

**Priority:** HIGH (Security Critical)
**Estimated Effort:** 2-3 days
**Risk Level:** Low

---

## Executive Summary

This plan adds Cross-Site Request Forgery (CSRF) protection to the Farkle Ten application. The codebase currently has **no CSRF protection**, leaving all state-changing AJAX endpoints vulnerable to cross-site attacks.

---

## Current State

### Session Handling
- Cookie settings: `httponly=1`, `samesite=Lax`, `secure` based on HTTPS
- The `samesite=Lax` provides partial protection but does NOT protect AJAX POST requests

### Vulnerable Endpoints (40+ actions in farkle_fetch.php)

| Action | Risk Level | Description |
|--------|------------|-------------|
| `logout` | Medium | Session termination |
| `saveoptions` | High | Changes player settings |
| `quitgame` | High | Counts as loss, affects stats |
| `farkleroll` | High | Game state manipulation |
| `farklepass` | High | Game state manipulation |
| `resetpass` | Critical | Password reset |

---

## Implementation Plan

### Phase 1: Token Generation Functions

Add to `/includes/baseutil.php`:

```php
/**
 * Generate or retrieve CSRF token for current session
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token using timing-safe comparison
 */
function csrf_verify(string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate CSRF token (call on login/logout)
 */
function csrf_regenerate(): string {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
```

### Phase 2: Smarty Template Integration

Assign token in baseutil.php after Smarty initialization:
```php
$smarty->assign('csrf_token', csrf_token());
```

Add hidden input to `/templates/farkle.tpl`:
```smarty
<!-- CSRF Token for AJAX requests -->
<input type="hidden" value="{$csrf_token}" id="csrf_token">
```

### Phase 3: Server-Side Validation

Add to `/wwwroot/farkle_fetch.php`:

```php
$csrf_exempt_actions = ['login', 'register', 'forgotpass', 'resetpass'];
$read_only_actions = ['getlobbyinfo', 'getfriends', 'getplayerinfo', 'getleaderboard',
                      'getnewgameinfo', 'farklegetupdate', 'getachievements'];

if (isset($p['action']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $p['action'];

    if (!in_array($action, $csrf_exempt_actions) && !in_array($action, $read_only_actions)) {
        $submitted_token = $p['csrf_token'] ?? '';

        if (!csrf_verify($submitted_token)) {
            error_log("CSRF validation failed for action: {$action}");
            echo json_encode([
                'Error' => 'Session expired. Please refresh the page.',
                'CSRFError' => true
            ]);
            exit(0);
        }
    }
}
```

### Phase 4: JavaScript AJAX Integration

Update `/wwwroot/js/util.js`:

```javascript
function FarkleAjaxCall(func, params) {
    // Get CSRF token from hidden input
    var csrfInput = document.getElementById('csrf_token');
    if (csrfInput && csrfInput.value) {
        params += '&csrf_token=' + encodeURIComponent(csrfInput.value);
    }
    AjaxCallPost(gAjaxUrl, func, params);
}
```

### Phase 5: Token Regeneration

In `/wwwroot/farkleLogin.php`, regenerate token on login:
```php
function LoginSuccess($pInfo, $remember = 1) {
    // ... existing code ...
    csrf_regenerate();
    return 1;
}
```

### Phase 6: Client-Side Error Handling

Update response parser in `/wwwroot/js/util.js`:

```javascript
function FarkleParseAjaxResponse(data) {
    var jsonData = farkleParseJSON(data);

    if (jsonData.CSRFError) {
        farkleAlert('Your session has expired. The page will reload.', 'orange');
        setTimeout(function() {
            location.reload();
        }, 2000);
        return 0;
    }
    // ... rest of existing code
}
```

---

## Testing Approach

### Manual Tests
1. Log in, verify hidden input `#csrf_token` exists
2. Perform game action, verify it succeeds
3. Modify token in dev tools, verify action fails
4. Log out and back in, verify token changed

### Automated Test
```php
// Test: Request without CSRF token should fail
$result = make_request('action=quitgame&gameid=999');
assert_contains($result, 'Session expired', 'Missing CSRF should fail');

// Test: Read-only request without token should succeed
$result = make_request('action=getlobbyinfo');
assert_not_contains($result, 'Session expired', 'Read-only should work');
```

---

## Security Properties

- **Token Length:** 64 hex characters (256 bits of entropy)
- **Generation:** `bin2hex(random_bytes(32))` - cryptographically secure
- **Storage:** Session only (not in cookies/localStorage)
- **Comparison:** `hash_equals()` prevents timing attacks

---

## Rollback Plan

Quick disable if issues arise:
```php
$csrf_enabled = false; // Set to true when ready
if (!$csrf_enabled) {
    // Skip CSRF validation
}
```

---

## Implementation Checklist

- [ ] Add CSRF functions to `/includes/baseutil.php`
- [ ] Assign `csrf_token` to Smarty
- [ ] Add hidden input to `/templates/farkle.tpl`
- [ ] Update `FarkleAjaxCall()` in `/wwwroot/js/util.js`
- [ ] Add validation logic to `/wwwroot/farkle_fetch.php`
- [ ] Regenerate token on login/logout
- [ ] Add CSRF error handling in JS
- [ ] Run manual and automated tests
