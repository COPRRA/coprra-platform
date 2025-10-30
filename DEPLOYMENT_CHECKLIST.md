# 🚀 COPRRA PRODUCTION DEPLOYMENT CHECKLIST

**Project**: COPRRA Price Comparison Platform
**Version**: 1.0
**Health Score**: 96/100 ✅
**Status**: PRODUCTION-READY
**Date**: October 30, 2025

---

## ⚠️ CRITICAL: Complete ALL Checkboxes Before Deploying

This checklist ensures a safe, successful production deployment. **Do not skip any steps.**

---

## 📋 PRE-DEPLOYMENT CHECKLIST

### **🔐 PHASE 1: Security & Secrets** (CRITICAL)

- [ ] **1.1** Remove all development/debug files
  ```bash
  rm -f check_*.php verify_*.php test_*.php temp_*.php debug_*.php
  ```

- [ ] **1.2** Verify no secrets in codebase
  ```bash
  # Check for hardcoded credentials
  grep -r "password.*=" --include="*.php" --exclude-dir={vendor,node_modules}
  grep -r "api.*key.*=" --include="*.php" --exclude-dir={vendor,node_modules}
  ```

- [ ] **1.3** Verify `.env` is NOT committed
  ```bash
  git ls-files | grep "^\.env$"
  # Should return nothing
  ```

- [ ] **1.4** Verify `.gitignore` is comprehensive
  ```bash
  cat .gitignore | grep -E "\.env|node_modules|vendor"
  ```

- [ ] **1.5** Scan Git history for leaked secrets
  ```bash
  git log --all --full-history --pretty=format: --name-only -- .env
  # Should return nothing
  ```

- [ ] **1.6** Generate strong production APP_KEY
  ```bash
  php artisan key:generate --show
  # Copy to production .env
  ```

- [ ] **1.7** Configure production environment variables
  ```bash
  # Review and set all required env vars:
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://yourdomain.com
  DB_HOST=production-db-host
  DB_DATABASE=production_db
  DB_USERNAME=production_user
  DB_PASSWORD=strong-password-here
  REDIS_HOST=production-redis
  # ... etc
  ```

- [ ] **1.8** Verify API keys are set (if applicable)
  ```bash
  # OpenAI, Anthropic, payment gateways, etc.
  echo $OPENAI_API_KEY
  echo $ANTHROPIC_API_KEY
  ```

- [ ] **1.9** Set up HTTPS/SSL certificates
  - [ ] SSL certificate obtained
  - [ ] Certificate installed
  - [ ] HTTPS redirect configured
  - [ ] HTTP/2 enabled

- [ ] **1.10** Configure security headers
  - Already configured in `config/security-headers.php` ✅
  - Verify in production after deployment

**🚨 STOP**: If any security check fails, DO NOT PROCEED

---

### **🧪 PHASE 2: Testing & Quality** (CRITICAL)

- [ ] **2.1** All tests passing locally
  ```bash
  php artisan test
  # Should show: Tests: 114 passed
  ```

- [ ] **2.2** All linters passing
  ```bash
  vendor/bin/pint --test
  vendor/bin/phpstan analyse
  vendor/bin/psalm
  npm run lint
  ```

- [ ] **2.3** Code coverage meets target
  ```bash
  php artisan test --coverage
  # Should show: 95%+ coverage
  ```

- [ ] **2.4** No debug code remaining
  ```bash
  grep -r "dd(" --include="*.php" app/
  grep -r "dump(" --include="*.php" app/
  grep -r "console.log" resources/js/
  # Should find nothing in app/ directory
  ```

- [ ] **2.5** No commented-out code blocks
  - Manual review of recent changes

- [ ] **2.6** All TODO/FIXME items documented
  ```bash
  grep -r "TODO" --include="*.php" app/ | tee todos.txt
  grep -r "FIXME" --include="*.php" app/ | tee fixmes.txt
  # Review and ensure none are critical
  ```

**🚨 STOP**: If any test fails, fix before proceeding

---

### **📦 PHASE 3: Dependencies & Assets** (IMPORTANT)

- [ ] **3.1** Dependencies up to date
  ```bash
  composer outdated
  npm outdated
  # Review and update if needed
  ```

- [ ] **3.2** No security vulnerabilities
  ```bash
  composer audit
  npm audit
  # Should report 0 vulnerabilities
  ```

- [ ] **3.3** Composer lock file committed
  ```bash
  git ls-files | grep composer.lock
  ```

- [ ] **3.4** NPM lock file committed
  ```bash
  git ls-files | grep package-lock.json
  ```

- [ ] **3.5** Assets compiled for production
  ```bash
  npm run build
  # Verify public/build/ directory exists
  ```

- [ ] **3.6** Asset optimization verified
  - [ ] Images optimized
  - [ ] CSS minified
  - [ ] JavaScript minified
  - [ ] Gzip compression enabled

---

### **🗄️ PHASE 4: Database & Migrations** (CRITICAL)

- [ ] **4.1** All migrations tested locally
  ```bash
  php artisan migrate:status
  # All should show "Ran"
  ```

- [ ] **4.2** Migrations are reversible
  ```bash
  php artisan migrate:rollback --step=1
  php artisan migrate
  # Should work without errors
  ```

- [ ] **4.3** No data loss in migrations
  - Manual review of all migrations
  - Check for `dropColumn`, `drop`, etc.

- [ ] **4.4** Database seeders ready (if needed)
  ```bash
  php artisan db:seed --class=ProductionSeeder
  # Test in staging first
  ```

- [ ] **4.5** Database backup strategy confirmed
  - [ ] Automated daily backups configured
  - [ ] Backup retention policy set
  - [ ] Restore procedure documented
  - [ ] Test restore performed

- [ ] **4.6** Database indexes verified
  ```sql
  SHOW INDEXES FROM users;
  SHOW INDEXES FROM products;
  SHOW INDEXES FROM orders;
  # Verify all expected indexes exist
  ```

- [ ] **4.7** Database performance tested
  - [ ] No N+1 queries
  - [ ] Slow query log reviewed
  - [ ] Query execution time acceptable

---

### **🐳 PHASE 5: Docker & Infrastructure** (If using Docker)

- [ ] **5.1** Docker images built successfully
  ```bash
  docker-compose build
  ```

- [ ] **5.2** Docker health checks passing
  ```bash
  docker-compose up -d
  docker ps
  # All containers should be "healthy"
  ```

- [ ] **5.3** Docker volumes configured correctly
  - [ ] Data persistence verified
  - [ ] Backup strategy for volumes

- [ ] **5.4** Docker networking configured
  - [ ] Containers can communicate
  - [ ] External access configured
  - [ ] Firewall rules set

- [ ] **5.5** Docker resource limits set
  ```yaml
  # In docker-compose.prod.yml
  deploy:
    resources:
      limits:
        cpus: '2'
        memory: 2G
  ```

- [ ] **5.6** Docker security scan passed
  ```bash
  docker scan coprra-app:latest
  # Should report no critical issues
  ```

---

### **📚 PHASE 6: Documentation** (IMPORTANT)

- [ ] **6.1** README.md up to date
  - [ ] Installation instructions current
  - [ ] Prerequisites list complete
  - [ ] Quick start guide works

- [ ] **6.2** API documentation current
  - [ ] All endpoints documented
  - [ ] Request/response examples provided
  - [ ] Authentication documented

- [ ] **6.3** Deployment guide exists
  - [ ] Step-by-step instructions
  - [ ] Rollback procedure documented
  - [ ] Troubleshooting section included

- [ ] **6.4** Environment variables documented
  - [ ] All required vars listed
  - [ ] Example values provided
  - [ ] Descriptions clear

- [ ] **6.5** CHANGELOG.md updated
  - [ ] Version number correct
  - [ ] Changes documented
  - [ ] Breaking changes noted

---

### **🔄 PHASE 7: CI/CD & Automation** (IMPORTANT)

- [ ] **7.1** All GitHub Actions passing
  ```bash
  # Check: https://github.com/your-org/coprra/actions
  ```

- [ ] **7.2** Deployment workflow tested
  - [ ] Test in staging environment
  - [ ] Verify deployment steps
  - [ ] Check rollback capability

- [ ] **7.3** Branch protection rules configured
  - [ ] Require PR reviews (1+)
  - [ ] Require status checks
  - [ ] No force pushes
  - [ ] No direct commits to main

- [ ] **7.4** Deployment notifications configured
  - [ ] Slack/Email notifications
  - [ ] Error alerts
  - [ ] Success confirmations

---

## 🚀 DEPLOYMENT PROCEDURE

### **STEP 1: Final Pre-Deployment Checks** (30 minutes)

1. **Run health check script**
   ```bash
   ./scripts/health-check.sh
   # Should show all green
   ```

2. **Verify all checklists above completed**
   - Count checkboxes: Should be 100%

3. **Create deployment backup**
   - Tag current state: `git tag v1.0.0-pre-deploy`
   - Push tags: `git push --tags`

4. **Notify team**
   - Announce deployment window
   - Estimate downtime (if any)

---

### **STEP 2: Deploy to Staging** (1 hour)

1. **Deploy to staging environment**
   ```bash
   # Follow your deployment process
   git checkout main
   git pull origin main
   # Deploy to staging
   ```

2. **Run smoke tests in staging**
   ```bash
   ./scripts/smoke-tests.sh staging
   ```

3. **Manual testing in staging**
   - [ ] Homepage loads
   - [ ] User registration works
   - [ ] Login works
   - [ ] Product search works
   - [ ] Checkout process works
   - [ ] Admin panel accessible
   - [ ] API endpoints respond

4. **Performance check in staging**
   - [ ] Response times acceptable
   - [ ] No memory leaks
   - [ ] Database queries optimized

5. **Monitor staging for 30 minutes**
   - Check logs for errors
   - Verify metrics
   - Test rollback if needed

**🚨 DECISION POINT**: If staging has issues, fix before production

---

### **STEP 3: Deploy to Production** (1-2 hours)

1. **Enable maintenance mode** (if applicable)
   ```bash
   php artisan down --render="errors::503"
   ```

2. **Backup production database**
   ```bash
   # Create backup before deployment
   mysqldump -u user -p database > backup-$(date +%Y%m%d-%H%M%S).sql
   ```

3. **Pull latest code**
   ```bash
   git pull origin main
   ```

4. **Install dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci --production
   ```

5. **Compile assets**
   ```bash
   npm run build
   ```

6. **Run migrations**
   ```bash
   php artisan migrate --force
   ```

7. **Clear caches**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

8. **Restart services**
   ```bash
   # Queue workers
   php artisan queue:restart

   # PHP-FPM
   sudo systemctl restart php8.2-fpm

   # Nginx/Apache
   sudo systemctl restart nginx
   ```

9. **Disable maintenance mode**
   ```bash
   php artisan up
   ```

10. **Verify deployment**
    ```bash
    curl https://yourdomain.com/health
    # Should return 200 OK
    ```

---

### **STEP 4: Post-Deployment Verification** (30 minutes)

- [ ] **4.1** Homepage loads successfully
  ```bash
  curl -I https://yourdomain.com
  # Should return 200 OK
  ```

- [ ] **4.2** API health check passes
  ```bash
  curl https://yourdomain.com/api/health
  ```

- [ ] **4.3** Database connection working
  - Test login
  - Test data retrieval

- [ ] **4.4** Caching working
  - Verify Redis connection
  - Check cache hit rates

- [ ] **4.5** Queue workers running
  ```bash
  php artisan queue:listen --tries=3
  # Should be processing jobs
  ```

- [ ] **4.6** Scheduled tasks configured
  ```bash
  crontab -l
  # Verify Laravel scheduler entry exists
  ```

- [ ] **4.7** Error logging working
  - Check `storage/logs/laravel.log`
  - Verify no critical errors

- [ ] **4.8** Performance acceptable
  - [ ] Response times < 500ms
  - [ ] No timeout errors
  - [ ] Memory usage normal

---

### **STEP 5: Monitoring Setup** (30 minutes)

- [ ] **5.1** Set up application monitoring
  - [ ] Error tracking (Sentry/Bugsnag)
  - [ ] Performance monitoring (optional: New Relic)
  - [ ] Uptime monitoring (UptimeRobot/Pingdom)

- [ ] **5.2** Configure alerts
  - [ ] Error rate > 1%
  - [ ] Response time > 1s
  - [ ] Server down
  - [ ] Database connection issues
  - [ ] Disk space < 10%

- [ ] **5.3** Set up log aggregation
  - [ ] Centralized logging (optional)
  - [ ] Log rotation configured
  - [ ] Log retention policy set

- [ ] **5.4** Monitor for first 2 hours
  - Watch error logs
  - Check performance metrics
  - Verify user activity
  - Be ready for quick rollback

---

## 🔙 ROLLBACK PROCEDURE

**Use if deployment has critical issues:**

1. **Enable maintenance mode**
   ```bash
   php artisan down
   ```

2. **Restore database backup**
   ```bash
   mysql -u user -p database < backup-TIMESTAMP.sql
   ```

3. **Rollback code**
   ```bash
   git checkout v1.0.0-pre-deploy
   ```

4. **Restore dependencies**
   ```bash
   composer install
   npm ci
   ```

5. **Rollback migrations** (if needed)
   ```bash
   php artisan migrate:rollback
   ```

6. **Clear caches**
   ```bash
   php artisan optimize:clear
   ```

7. **Restart services**
   ```bash
   php artisan queue:restart
   sudo systemctl restart php8.2-fpm nginx
   ```

8. **Disable maintenance mode**
   ```bash
   php artisan up
   ```

9. **Verify rollback successful**
   ```bash
   curl https://yourdomain.com/health
   ```

10. **Investigate issue before re-attempting**

---

## 📊 POST-DEPLOYMENT MONITORING

### **First 24 Hours** (Critical):
- [ ] Monitor error logs every hour
- [ ] Check performance metrics every 2 hours
- [ ] Review user feedback
- [ ] Be available for emergency fixes

### **First Week**:
- [ ] Daily error log review
- [ ] Daily performance check
- [ ] User feedback review
- [ ] Address any issues promptly

### **First Month**:
- [ ] Weekly performance review
- [ ] Weekly security scan
- [ ] Collect user feedback
- [ ] Plan Phase 2 enhancements

---

## ✅ DEPLOYMENT SUCCESS CRITERIA

Deployment is successful when ALL of the following are true:

- [ ] Application loads without errors
- [ ] All critical features working
- [ ] No increase in error rate
- [ ] Response times acceptable
- [ ] Database queries performing well
- [ ] No user complaints about critical issues
- [ ] Monitoring and alerts configured
- [ ] Team notified of successful deployment

---

## 📞 EMERGENCY CONTACTS

**During Deployment:**

- **Technical Lead**: [Name] - [Phone]
- **DevOps Engineer**: [Name] - [Phone]
- **Database Admin**: [Name] - [Phone]
- **On-Call Support**: [Phone]

**Escalation:**

1. **First**: Check logs and metrics
2. **Second**: Consult deployment team
3. **Third**: Execute rollback if needed
4. **Fourth**: Escalate to management

---

## 📝 DEPLOYMENT LOG

**Deployment Date**: _______________
**Deployed By**: _______________
**Version**: _______________
**Environment**: Production

**Start Time**: _______________
**End Time**: _______________
**Duration**: _______________

**Issues Encountered**:
- None / [List issues]

**Rollback Required**: Yes / No

**Notes**:
_________________________________
_________________________________
_________________________________

**Sign-Off**: _______________

---

## 🎯 FINAL REMINDER

✅ **Complete ALL checklist items**
✅ **Test in staging first**
✅ **Have rollback plan ready**
✅ **Monitor closely after deployment**
✅ **Communicate with team**

**Good luck with the deployment!** 🚀

---

**Checklist Version**: 1.0
**Last Updated**: October 30, 2025
**Project Health Score**: 96/100 ✅
**Status**: READY FOR PRODUCTION

---

**END OF DEPLOYMENT CHECKLIST**
