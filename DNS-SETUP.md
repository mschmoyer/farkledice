# DNS Setup Guide - farkledice.com

Complete guide for connecting your custom domain `farkledice.com` to Heroku.

## Overview

- **Custom Domain:** farkledice.com
- **Heroku DNS Target:** metaphysical-marsupial-81rw0572bhuuzqa09akgz67c.herokudns.com
- **Strategy:** Use www.farkledice.com as primary, redirect apex domain via PHP

## Step 1: Configure Heroku

Add your custom domains to Heroku:

```bash
# Add the www subdomain (primary)
heroku domains:add www.farkledice.com -a farkledice

# Add the apex domain (will redirect to www)
heroku domains:add farkledice.com -a farkledice

# Verify domains were added
heroku domains -a farkledice
```

Expected output:
```
=== farkledice Custom Domains
Domain Name           DNS Target
────────────────────  ──────────────────────────────────────────────
farkledice.com        metaphysical-marsupial-81rw0572bhuuzqa09akgz67c.herokudns.com
www.farkledice.com    metaphysical-marsupial-81rw0572bhuuzqa09akgz67c.herokudns.com
```

### Verify ACM (Automatic Certificate Management)

Check that SSL certificates are enabled:

```bash
heroku certs:auto -a farkledice
```

Should show:
```
=== Automatic Certificate Management is enabled on farkledice
```

## Step 2: Configure AWS Route 53

Log into AWS Console → Route 53 → Hosted Zones → Select `farkledice.com`

### Create DNS Records

#### Record 1: www subdomain (CNAME)

**This is your primary domain.**

- Click **"Create record"**
- **Record name:** `www`
- **Record type:** `CNAME`
- **Value:** `metaphysical-marsupial-81rw0572bhuuzqa09akgz67c.herokudns.com`
- **TTL:** `300` (5 minutes)
- **Routing policy:** Simple routing
- Click **"Create records"**

#### Record 2: Apex domain (CNAME or ALIAS)

**Option A: If Route 53 allows CNAME at apex (try this first):**

- Click **"Create record"**
- **Record name:** Leave blank (for root domain)
- **Record type:** `CNAME`
- **Value:** `metaphysical-marsupial-81rw0572bhuuzqa09akgz67c.herokudns.com`
- **TTL:** `300`
- Click **"Create records"**

**Option B: If Route 53 rejects CNAME at apex:**

Route 53 may not allow CNAME records at the apex domain. In this case:

1. **Use ALIAS record** (if Route 53 supports it for external domains)
2. **OR**, skip this DNS record - the PHP redirect will handle it

The PHP code (added to `baseutil.php` and `index.php`) will automatically redirect:
- `http://farkledice.com` → `https://www.farkledice.com`
- `https://farkledice.com` → `https://www.farkledice.com`

## Step 3: Deploy Updated Code

The redirect code has been added to:
- `includes/baseutil.php` - Catches all pages that include this file
- `wwwroot/index.php` - Catches direct access to root

Deploy to Heroku:

```bash
git add includes/baseutil.php wwwroot/index.php DNS-SETUP.md
git commit -m "Add apex domain → www redirect for custom domain setup"
git push heroku modernization/phase-1:main
```

## Step 4: Wait for DNS Propagation

DNS changes can take up to 48 hours to propagate globally, but usually happen within:
- **5-15 minutes:** Most DNS servers
- **1-2 hours:** Conservative estimate
- **24-48 hours:** Maximum (rare)

### Check DNS Propagation

**Check www subdomain:**
```bash
dig www.farkledice.com

# Should show CNAME pointing to Heroku
# Look for: www.farkledice.com. 300 IN CNAME metaphysical-marsupial...
```

**Check apex domain:**
```bash
dig farkledice.com

# Should show CNAME or A record pointing to Heroku
```

**Check from multiple locations:**
- https://dnschecker.org
- Enter `www.farkledice.com` and check CNAME records globally

## Step 5: Test SSL Certificate

Once DNS propagates, Heroku ACM will automatically provision SSL certificates.

**Check certificate status:**
```bash
heroku certs:auto -a farkledice
```

Wait for:
```
Certificate details:
Expires At:               2027-XX-XX XX:XX UTC
Issuer:                   /C=US/O=Let's Encrypt/CN=R3
Starts At:                2026-XX-XX XX:XX UTC
Subject:                  /CN=farkledice.com
SSL certificate is verified and trusted.
```

**This can take:**
- 15-30 minutes after DNS propagates
- Up to a few hours in some cases

## Step 6: Verify Everything Works

### Test www subdomain:
```bash
curl -I https://www.farkledice.com
```

Should return:
```
HTTP/2 200
```

### Test apex domain redirect:
```bash
curl -I http://farkledice.com
```

Should return:
```
HTTP/1.1 301 Moved Permanently
Location: https://www.farkledice.com/
```

### Test in browser:

1. Visit `http://farkledice.com` → Should redirect to `https://www.farkledice.com`
2. Visit `https://farkledice.com` → Should redirect to `https://www.farkledice.com`
3. Visit `https://www.farkledice.com` → Should load the site with valid SSL

## Troubleshooting

### DNS not resolving

**Problem:** `dig www.farkledice.com` shows no results

**Solutions:**
1. Wait longer (DNS can take time)
2. Verify Route 53 records are correct
3. Check that Route 53 nameservers are correct at your domain registrar

**Check nameservers:**
```bash
dig NS farkledice.com
```

Should match the NS records in Route 53 hosted zone.

### SSL Certificate Not Provisioning

**Problem:** Certificate shows "Waiting for domain" or "Failed"

**Causes:**
1. DNS not fully propagated yet
2. DNS records pointing to wrong target
3. CAA records blocking Let's Encrypt

**Check DNS is correct:**
```bash
dig www.farkledice.com CNAME
```

**Force certificate refresh:**
```bash
heroku certs:auto:refresh -a farkledice
```

### Redirect Loop

**Problem:** Browser shows "too many redirects"

**Cause:** Check `.htaccess` for conflicting redirects

**Solution:** Review `.htaccess` in `wwwroot/` and ensure no conflicting HTTPS redirects

### Site Not Loading

**Problem:** DNS resolves but site doesn't load

**Check Heroku app status:**
```bash
heroku ps -a farkledice
```

**Check logs:**
```bash
heroku logs --tail -a farkledice
```

## DNS Record Summary

After setup, your Route 53 records should look like:

| Record Name | Type  | Value |
|-------------|-------|-------|
| (blank)     | NS    | (Route 53 nameservers - auto-created) |
| (blank)     | SOA   | (Route 53 SOA - auto-created) |
| www         | CNAME | metaphysical-marsupial-81rw0572bhuuzqa09akgz67c.herokudns.com |
| (blank)     | CNAME | metaphysical-marsupial-81rw0572bhuuzqa09akgz67c.herokudns.com |

**Note:** If you can't create CNAME at apex, it's okay - the PHP redirect will handle it!

## How the Redirect Works

### Code Location

**File:** `includes/baseutil.php` (lines 11-25)
```php
// Redirect apex domain to www subdomain
if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];

    if ($host === 'farkledice.com') {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $protocol . '://www.farkledice.com' . $uri);
        exit();
    }
}
```

### What It Does

1. **Checks the hostname** - Is it exactly `farkledice.com`?
2. **Preserves protocol** - Maintains HTTP or HTTPS
3. **Preserves path** - Keeps `/game`, `/leaderboard`, etc.
4. **301 redirect** - Tells search engines this is permanent
5. **Exits** - Stops execution to prevent double processing

### Examples

- `http://farkledice.com/` → `https://www.farkledice.com/`
- `http://farkledice.com/game?id=123` → `https://www.farkledice.com/game?id=123`
- `https://farkledice.com/leaderboard` → `https://www.farkledice.com/leaderboard`

## Timeline Expectations

| Step | Expected Time |
|------|---------------|
| Add domains to Heroku | Immediate |
| Create Route 53 records | Immediate |
| DNS propagation | 5 minutes - 2 hours |
| SSL certificate provisioned | 15-30 minutes after DNS |
| Full availability | 30 minutes - 3 hours total |

## SEO Considerations

The 301 redirect ensures:
- ✅ Search engines understand www is the canonical domain
- ✅ Link equity transfers from apex to www
- ✅ No duplicate content penalties
- ✅ Consistent branding (always shows www.farkledice.com)

## Next Steps After Setup

1. Update links in any marketing materials to use `www.farkledice.com`
2. Submit sitemap to Google Search Console for both:
   - farkledice.com
   - www.farkledice.com
3. Set www.farkledice.com as preferred domain in Search Console
4. Monitor SSL certificate renewal (automatic via Heroku ACM)

## Support Resources

- **Heroku Custom Domains:** https://devcenter.heroku.com/articles/custom-domains
- **Heroku ACM:** https://devcenter.heroku.com/articles/automated-certificate-management
- **Route 53 DNS:** https://docs.aws.amazon.com/route53/
- **DNS Checker:** https://dnschecker.org
