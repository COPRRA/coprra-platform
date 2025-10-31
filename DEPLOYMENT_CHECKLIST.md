# Hostinger Deployment Checklist

## Pre-Deployment Preparation

### Hostinger Account Setup
- [ ] Hostinger account active and accessible
- [ ] cPanel credentials available
- [ ] Domain name configured and pointing to Hostinger nameservers
- [ ] SSL certificate available (Let's Encrypt via cPanel)

### Local Preparation
- [ ] Latest code pulled from GitHub
- [ ] All tests passing locally
- [ ] `.env.example` file updated
- [ ] Database migrations tested locally
- [ ] Deployment package created

## Phase 1: File Upload

### Backup Existing Files (if any)
- [ ] Created backup folder in cPanel: `public_html_backup_[date]`
- [ ] Moved existing files to backup folder
- [ ] Noted any custom configurations

### Upload Files
- [ ] Logged into Hostinger cPanel
- [ ] Navigated to File Manager → public_html/
- [ ] Uploaded `coprra-hostinger.tar.gz` (or used FTP)
- [ ] Extracted archive successfully
- [ ] Deleted .tar.gz file after extraction
- [ ] Verified all folders present (app/, config/, public/, etc.)

## SUCCESS CRITERIA

Deployment complete when:
- ✅ All checklist items marked
- ✅ Production URL live
- ✅ Status endpoint operational
- ✅ No critical errors
- ✅ All features tested

---

**Deployment Date:** _________________
**Production URL:** https://_________________
**Status:** ⏳ Pending Hostinger Credentials
