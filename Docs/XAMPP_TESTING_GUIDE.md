# PawaPay Integration - XAMPP Testing Guide

**Version:** 1.0.0
**Date:** September 25, 2025
**Environment:** XAMPP (Windows/Linux), PHP 7.4+

---

## 🎯 **Overview**

This guide provides step-by-step instructions for testing the PawaPay integration using XAMPP before deploying to production. The testing infrastructure allows you to validate all components independently.

---

## 📋 **Prerequisites**

### **System Requirements**
- ✅ **XAMPP** installed and running
- ✅ **PHP 7.4+** with required extensions
- ✅ **MySQL/MariaDB** database configured
- ✅ **Composer** for dependency management
- ✅ **cURL** extension enabled
- ✅ **OpenSSL** extension enabled

### **PawaPay Account**
- ✅ **Sandbox API credentials** from PawaPay dashboard
- ✅ **Webhook endpoint** configured in PawaPay dashboard
- ✅ **Test phone numbers** for sandbox testing

---

## 🚀 **Quick Start**

### **Step 1: Start XAMPP**
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Verify services are running (green indicators)

### **Step 2: Configure Environment**
1. Copy environment template:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` file with your credentials:
   ```env
   # PawaPay Configuration
   PAWAPAY_PUBLIC_KEY=your_sandbox_public_key
   PAWAPAY_SECRET_KEY=your_sandbox_secret_key
   PAWAPAY_WEBHOOK_SECRET=your_webhook_secret
   PAWAPAY_ENVIRONMENT=sandbox

   # Database Configuration
   DB_HOSTNAME=localhost
   DB_DATABASE=pawapay_test
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### **Step 3: Run Initial Setup**
```bash
# Install dependencies
php composer.phar install

# Run database migrations
php database/MigrationRunner.php up

# Test basic setup
php TEST_SETUP.php
```

### **Step 4: Start Development Server**
```bash
# Start PHP built-in server
php -S localhost:8000 index.php
```

---

## 🧪 **Testing Sequence**

### **Phase 1: Configuration Testing**
**URL:** `http://localhost:8000/tests/test_config.php`

**What it tests:**
- ✅ Environment file validation
- ✅ Database connectivity
- ✅ PawaPay configuration
- ✅ API token validation

**Expected Results:**
- All green checkmarks ✅
- No red error indicators ❌

### **Phase 2: API Connectivity Testing**
**URL:** `http://localhost:8000/test_api_connection.php`

**What it tests:**
- ✅ Bearer token authentication
- ✅ PawaPay sandbox API connectivity
- ✅ Provider/MNO fetching
- ✅ API response validation

**Expected Results:**
```json
{
    "success": true,
    "message": "Successfully connected to PawaPay API. Found 3 countries with providers.",
    "details": {
        "environment": "sandbox",
        "base_url": "https://api.sandbox.pawapay.io",
        "countries_available": 3
    }
}
```

### **Phase 3: Payment Flow Testing**
**URL:** `http://localhost:8000/tests/test_payment.php`

**What it tests:**
- ✅ Phone number validation
- ✅ Mobile operator selection
- ✅ Deposit initiation
- ✅ API request/response handling

**Test Data:**
- **MTN:** `260763456789`
- **Airtel:** `260976123456`
- **Zamtel:** `260951234567`

**Expected Results:**
- Payment initiated successfully
- Deposit ID generated
- Proper error handling for failures

### **Phase 4: Webhook Testing**
**URL:** `http://localhost:8000/tests/test_webhook.php`

**What it tests:**
- ✅ Webhook signature generation
- ✅ Webhook endpoint accessibility
- ✅ Payment status simulation
- ✅ Error scenario handling

**Test Scenarios:**
- **COMPLETED:** Successful payment
- **FAILED:** Payment failure simulation
- **PROCESSING:** In-progress payment
- **IN_RECONCILIATION:** Reconciliation status

---

## 🔧 **Troubleshooting**

### **Common Issues**

#### **1. "Connection Refused" Error**
**Problem:** Cannot connect to localhost
**Solution:**
```bash
# Check if Apache is running in XAMPP
# Restart XAMPP services
# Verify port 80 is not blocked by firewall
```

#### **2. "Database Connection Failed"**
**Problem:** Cannot connect to MySQL
**Solution:**
```bash
# Start MySQL service in XAMPP
# Check database credentials in .env
# Verify database exists: CREATE DATABASE pawapay_test;
```

#### **3. "API Connection Failed"**
**Problem:** Cannot connect to PawaPay API
**Solution:**
```bash
# Verify API credentials in .env
# Check internet connectivity
# Confirm using sandbox environment
```

#### **4. "CSRF Token Mismatch"**
**Problem:** Webhook blocked by CSRF protection
**Solution:**
```bash
# Check if 'webhook/pawapay' is in Filters.php CSRF exceptions
# Restart Apache after configuration changes
```

### **Debug Commands**

#### **Check PHP Configuration:**
```bash
php -m | grep -E "(curl|openssl|pdo|mbstring)"
```

#### **Test Database Connection:**
```bash
php -r "
try {
    \$pdo = new PDO('mysql:host=localhost;dbname=pawapay_test', 'root', '');
    echo 'Database: ✅ Connected';
} catch (Exception \$e) {
    echo 'Database: ❌ ' . \$e->getMessage();
}
"
```

#### **Test API Connectivity:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     https://api.sandbox.pawapay.io/v2/active-conf?country=ZMB
```

#### **Check File Permissions:**
```bash
# Ensure web server can read/write files
chmod -R 755 app/
chmod -R 755 tests/
chmod 644 .env
```

---

## 📊 **Test Results Interpretation**

### **Configuration Test Results**
- **✅ All Green:** Ready for API testing
- **⚠️ Yellow Warnings:** Minor issues, can proceed
- **❌ Red Errors:** Must fix before continuing

### **API Connection Results**
- **✅ Success:** API connectivity working
- **❌ RequestException:** Check credentials and network
- **❌ ConfigurationException:** Fix .env file

### **Payment Test Results**
- **✅ Success:** Payment flow working
- **❌ Invalid Phone:** Check phone number format
- **❌ Provider Error:** Verify operator selection

### **Webhook Test Results**
- **✅ HTTP 200:** Webhook processing working
- **❌ HTTP 500:** Check server configuration
- **❌ Signature Error:** Verify webhook secret

---

## 🔒 **Security Considerations**

### **During Testing:**
- ✅ Use **sandbox environment** only
- ✅ Use **test API credentials**
- ✅ Monitor **error logs** for issues
- ✅ Test with **small amounts** only

### **Environment Security:**
```bash
# Never commit real credentials
git status  # Check for .env in staging
git rm --cached .env  # Remove from git if needed

# Use strong test credentials
# Rotate API keys regularly
# Monitor webhook calls
```

---

## 📈 **Performance Testing**

### **Load Testing:**
```bash
# Install Apache Bench (ab)
ab -n 100 -c 10 http://localhost:8000/tests/test_config.php

# Expected Results:
# Requests per second: >= 10
# Time per request: <= 100ms
# Failed requests: 0
```

### **Concurrent Testing:**
```bash
# Test multiple simultaneous requests
for i in {1..5}; do
    curl -s http://localhost:8000/test_api_connection.php &
done
wait
```

---

## 🚨 **Emergency Procedures**

### **If Tests Fail:**
1. **Check XAMPP Services:**
   ```bash
   # Restart services
   sudo service apache2 restart  # Linux
   # Or use XAMPP Control Panel (Windows)
   ```

2. **Reset Database:**
   ```bash
   php database/MigrationRunner.php down
   php database/MigrationRunner.php up
   ```

3. **Clear Cache:**
   ```bash
   # Clear PHP cache
   php -r "opcache_reset();"

   # Clear browser cache
   # Hard refresh (Ctrl+F5)
   ```

### **If System Unstable:**
1. **Stop all services**
2. **Backup current state**
3. **Restore from backup**
4. **Re-run setup**

---

## 📞 **Support & Resources**

### **Documentation:**
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Production Readiness Checklist](PRODUCTION_READINESS_CHECKLIST.md)
- [XAMPP Test Results](XAMPP_TEST_RESULTS.md)

### **Test Files:**
- `tests/test_config.php` - Configuration validation
- `test_api_connection.php` - API connectivity
- `tests/test_payment.php` - Payment flow testing
- `tests/test_webhook.php` - Webhook simulation

### **Logs:**
- **PHP Errors:** `logs/php_errors.log`
- **PawaPay Logs:** `logs/pawapay.log`
- **Database Logs:** MySQL error logs

---

## ✅ **Testing Checklist**

### **Before Starting:**
- [ ] XAMPP services running
- [ ] Environment file configured
- [ ] Database created and accessible
- [ ] API credentials available

### **After Each Test Phase:**
- [ ] Configuration test passed
- [ ] API connection successful
- [ ] Payment flow working
- [ ] Webhook processing functional

### **Final Validation:**
- [ ] All tests pass consistently
- [ ] No errors in logs
- [ ] Performance acceptable
- [ ] Security measures working

---

## 🎉 **Next Steps After Testing**

### **Ready for Production:**
1. **Update Environment:**
   ```env
   PAWAPAY_ENVIRONMENT=production
   PAWAPAY_PUBLIC_KEY=your_production_key
   PAWAPAY_SECRET_KEY=your_production_secret
   ```

2. **Configure Production Webhook:**
   - Update PawaPay dashboard with production webhook URL
   - Verify webhook signature with production secret

3. **Deploy to Production:**
   - Backup current system
   - Deploy tested code
   - Monitor initial transactions

### **Production Monitoring:**
- Monitor payment success rates
- Track webhook delivery
- Alert on error spikes
- Regular security audits

---

**Generated:** September 25, 2025
**Status:** XAMPP Testing Infrastructure Complete
**Ready for:** Production deployment preparation

*This guide provides comprehensive XAMPP testing procedures for the PawaPay integration. Follow each phase sequentially for best results.*
