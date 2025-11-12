# DKIM Setup Complete Documentation

**Date:** 2025-01-27  
**Status:** ⏳ Pending DNS and Server Configuration  
**Domain:** coprra.com

## Overview

This document provides complete instructions for setting up DKIM (DomainKeys Identified Mail) for email authentication on coprra.com. DKIM helps prevent email spoofing and improves email deliverability.

## Current Status

### ✅ Completed
- ✅ Mail server identified: **Exim**
- ✅ DKIM key generation script created (`generate_dkim_keys.sh`)
- ✅ Private key generated and ready for upload
- ✅ Complete setup instructions documented

### ⏳ Pending (Requires Root Access or Hosting Support)
- ⏳ Private key uploaded to server
- ⏳ DNS TXT record added
- ⏳ Exim configuration updated
- ⏳ Exim service restarted

## Prerequisites

- SSH access to production server
- Root access (or hosting support) for server configuration
- Access to DNS management (Hostinger control panel)
- Domain: `coprra.com`

## Step-by-Step Setup

### Step 1: Generate DKIM Keys (If Not Already Done)

**Option A: Generate Locally (Recommended)**

```bash
# Run the key generation script
bash generate_dkim_keys.sh
```

This creates:
- `dkim_keys/coprra.com/default.private` - Private key (keep secure!)
- `dkim_keys/coprra.com/default.public` - Public key (for DNS)

**Option B: Use Online Generator**

1. Visit: https://www.dnswatch.info/dkim/create
2. Enter domain: `coprra.com`
3. Selector: `default`
4. Download keys

### Step 2: Upload Private Key to Server

```bash
# Upload private key to server
scp -P 65002 dkim_keys/coprra.com/default.private u990109832@45.87.81.218:/tmp/
```

### Step 3: Configure Server (Requires Root Access)

**SSH into server:**
```bash
ssh -p 65002 u990109832@45.87.81.218
```

**On server (with root/sudo access):**
```bash
# Create DKIM keys directory
sudo mkdir -p /etc/opendkim/keys/coprra.com

# Move private key to proper location
sudo mv /tmp/default.private /etc/opendkim/keys/coprra.com/

# Set proper ownership and permissions
sudo chown opendkim:opendkim /etc/opendkim/keys/coprra.com/default.private
sudo chmod 600 /etc/opendkim/keys/coprra.com/default.private
```

**Note:** If `opendkim` user doesn't exist, use `mail` or `exim` user instead:
```bash
sudo chown mail:mail /etc/opendkim/keys/coprra.com/default.private
# OR
sudo chown exim:exim /etc/opendkim/keys/coprra.com/default.private
```

### Step 4: Configure Exim

**Edit Exim configuration:**
```bash
sudo nano /etc/exim4/exim4.conf
```

**Add DKIM configuration:**
```exim
# DKIM Configuration for coprra.com
dkim_private_key = /etc/opendkim/keys/coprra.com/default.private
dkim_selector = default
dkim_domain = coprra.com
```

**Restart Exim:**
```bash
sudo systemctl restart exim4
# OR
sudo service exim4 restart
```

**Verify Exim is running:**
```bash
sudo systemctl status exim4
```

### Step 5: Add DNS TXT Record

**Get the public key from the generated keys:**

If you generated keys locally, check `dkim_keys/coprra.com/default.public` or run:
```bash
cat dkim_keys/coprra.com/default.public
```

**Format the DNS record:**

The DNS TXT record should be:
```
Type: TXT
Name: default._domainkey.coprra.com
TTL: 3600
Value: v=DKIM1; k=rsa; p=[PUBLIC_KEY_CONTENT]
```

**Add DNS record in Hostinger:**

1. Log in to Hostinger control panel
2. Go to DNS Management
3. Add new TXT record:
   - **Name:** `default._domainkey`
   - **Type:** `TXT`
   - **TTL:** `3600`
   - **Value:** `v=DKIM1; k=rsa; p=[PUBLIC_KEY]`
4. Save changes

**Note:** Remove line breaks from the public key when adding to DNS.

### Step 6: Verify DNS Propagation

**Wait for DNS propagation (5-60 minutes), then test:**

```bash
# Test DNS record
dig +short TXT default._domainkey.coprra.com

# Expected output should include:
# "v=DKIM1; k=rsa; p=[PUBLIC_KEY]"
```

**Online DNS checkers:**
- https://mxtoolbox.com/dkim.aspx
- https://www.dnswatch.info/dkim/check

### Step 7: Test Email Signing

**Send a test email from the server:**

```bash
# Send test email
echo "DKIM test email" | mail -s "DKIM Test" your-email@example.com
```

**Check email headers:**

In the received email, look for:
```
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed; d=coprra.com;
    s=default; ...
```

**Online email header analyzers:**
- https://mxtoolbox.com/EmailHeaders.aspx
- Gmail: View email → Show original

## Troubleshooting

### Issue: DNS Record Not Found

**Symptoms:**
- `dig` command returns no results
- DNS checker shows "No DKIM record found"

**Solutions:**
1. Wait longer for DNS propagation (can take up to 48 hours)
2. Verify DNS record was added correctly
3. Check for typos in DNS record name/value
4. Try different DNS servers: `dig @8.8.8.8 TXT default._domainkey.coprra.com`

### Issue: Exim Not Signing Emails

**Symptoms:**
- Emails sent but no DKIM-Signature header

**Solutions:**
1. Verify Exim configuration:
   ```bash
   sudo exim4 -bV | grep dkim
   ```
2. Check Exim logs:
   ```bash
   sudo tail -f /var/log/exim4/mainlog
   ```
3. Verify private key permissions:
   ```bash
   ls -la /etc/opendkim/keys/coprra.com/default.private
   ```
4. Test DKIM signing:
   ```bash
   sudo opendkim-testkey -d coprra.com -s default
   ```

### Issue: Permission Denied

**Symptoms:**
- Cannot create directories or move files
- Permission errors when accessing keys

**Solutions:**
1. Use `sudo` for system directories
2. Check current user permissions
3. Verify key file ownership matches mail server user

## Alternative: Using Hostinger Control Panel

If you don't have root access, contact Hostinger support:

1. **Request DKIM setup** via support ticket
2. **Provide information:**
   - Domain: `coprra.com`
   - Selector: `default`
   - Public key (from generated keys)
3. **Hostinger will:**
   - Configure server-side DKIM
   - Add DNS record
   - Test and verify

## Security Notes

- ⚠️ **Keep private key secure** - Never commit to Git or share publicly
- ⚠️ **Private key permissions** - Should be `600` (owner read/write only)
- ⚠️ **Private key location** - Should be in `/etc/opendkim/keys/` (system directory)
- ✅ **Public key** - Safe to share (goes in DNS)
- ✅ **DKIM keys directory** - Added to `.gitignore` to prevent accidental commits

## Files and Locations

### Local Files
- `generate_dkim_keys.sh` - Key generation script
- `dkim_keys/coprra.com/default.private` - Private key (local)
- `dkim_keys/coprra.com/default.public` - Public key (local)

### Server Files
- `/etc/opendkim/keys/coprra.com/default.private` - Private key (server)
- `/etc/exim4/exim4.conf` - Exim configuration

### DNS
- `default._domainkey.coprra.com` - DNS TXT record

## Verification Checklist

- [ ] DKIM keys generated
- [ ] Private key uploaded to server
- [ ] Private key has correct permissions (600)
- [ ] Private key has correct ownership
- [ ] Exim configuration updated
- [ ] Exim service restarted
- [ ] DNS TXT record added
- [ ] DNS propagation verified
- [ ] Test email sent
- [ ] DKIM-Signature header present in test email
- [ ] Online DKIM checker shows "PASS"

## Next Steps After Setup

1. **Monitor email deliverability** - Check spam rates
2. **Set up SPF record** - If not already configured
3. **Set up DMARC** - For additional email security
4. **Regular monitoring** - Check DKIM signing status periodically

## Related Documentation

- `generate_dkim_keys.sh` - Key generation script
- `MISSION_4_DKIM_INSTRUCTIONS.md` - Original setup instructions
- Hostinger documentation: https://www.hostinger.com/tutorials/dkim

## Support

If you encounter issues:
1. Check Exim logs: `/var/log/exim4/mainlog`
2. Verify DNS: Use online DKIM checkers
3. Contact Hostinger support if root access is not available
4. Review Exim documentation for your specific version

---

**Last Updated:** 2025-01-27  
**Status:** Documentation complete, awaiting DNS and server configuration

