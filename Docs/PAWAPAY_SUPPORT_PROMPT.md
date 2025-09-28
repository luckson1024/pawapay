# PawaPay AI Support Agent Prompt

## Environment Variable Loading Issue - Fix Request

**Date**: September 26, 2025
**Project**: PawaPay v2 Integration for Myzuwa.com
**Issue**: Environment variables not loading correctly through autoloader

### Problem Description

I have a PawaPay payment integration that's experiencing an issue with environment variable loading. The environment variables load correctly when running a test script directly, but fail when using the namespaced function through the Composer autoloader.

### Current Behavior

**✅ Working:**
- Direct execution of `test_env_loading.php` loads all 22 environment variables correctly
- Global `env()` function created by `config/bootstrap.php` works properly

**❌ Not Working:**
- Namespaced `Myzuwa\PawaPay\Support\env()` function returns "NOT_FOUND" for all variables

### Root Cause Analysis

The issue is in the `loadEnvFile()` function in `src/Support/env.php`. The `__DIR__` magic constant resolves to different paths depending on execution context:

- **Direct execution**: `__DIR__` → `src/Support` → `../../.env` ✅
- **Autoloader execution**: `__DIR__` → `vendor/composer` → `../../.env` ❌

### Code to Fix

**File**: `src/Support/env.php`

**Current problematic code:**
```php
function loadEnvFile()
{
    $envFile = __DIR__ . '/../../.env';
    $envVars = [];

    if (file_exists($envFile)) {
        // ... rest of function
    }
}
```

**Required fix:**
```php
function loadEnvFile()
{
    // Fix: Use project root instead of relative path from current directory
    $envFile = dirname(__DIR__, 2) . '/.env';
    $envVars = [];

    if (file_exists($envFile)) {
        // ... rest of function remains the same
    }
}
```

### Alternative Solutions (if the above doesn't work)

1. **Use absolute path:**
```php
function loadEnvFile()
{
    $envFile = $_SERVER['DOCUMENT_ROOT'] . '/../.env';
    // ... rest of function
}
```

2. **Use getcwd() with fallback:**
```php
function loadEnvFile()
{
    $envFile = getcwd() . '/.env';
    if (!file_exists($envFile)) {
        $envFile = __DIR__ . '/../../.env';
    }
    // ... rest of function
}
```

### Testing Instructions

After implementing the fix, please test:

1. **Direct execution test:**
```bash
php test_env_loading.php
```
Expected: Should load all 22 environment variables successfully

2. **Autoloader test:**
```bash
php -r "require_once 'vendor/autoload.php'; echo 'PAWAPAY_API_TOKEN: ' . Myzuwa\PawaPay\Support\env('PAWAPAY_API_TOKEN', 'NOT_FOUND') . PHP_EOL;"
```
Expected: Should return the actual API token value, not "NOT_FOUND"

3. **Regenerate autoloader:**
```bash
php composer.phar dump-autoload --optimize
```

### Environment Details

- **PHP Version**: 8.1+
- **OS**: Windows 10
- **Web Server**: XAMPP
- **Project Structure**:
  ```
  pawapay-v2-integration/
  ├── .env (contains PAWAPAY_API_TOKEN, PAWAPAY_API_URL, etc.)
  ├── src/
  │   └── Support/
  │       └── env.php (file to fix)
  ├── config/
  │   └── bootstrap.php (creates global env() function)
  ├── vendor/ (Composer dependencies)
  └── test_env_loading.php (working reference)
  ```

### Expected Outcome

After the fix, both execution methods should return the same results:
- `PAWAPAY_API_TOKEN: eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ...`
- All 22 environment variables should load correctly

### Additional Context

- The `.env` file exists and contains valid data
- The `config/bootstrap.php` file successfully creates a global `env()` function
- Composer autoloader is properly generated with 1349+ classes
- This is a path resolution issue, not a file access or syntax issue

### Priority Level

**HIGH** - This is blocking the PawaPay payment integration functionality.

---

*Please implement the fix in `src/Support/env.php` and test thoroughly. The issue is specifically with the `__DIR__` path resolution in the `loadEnvFile()` function.*
