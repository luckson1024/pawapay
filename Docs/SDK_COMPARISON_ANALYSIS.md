# PawaPay SDK Development Analysis & Recommendations

## Executive Summary

After analyzing the reference PawaPay SDK implementation by katorymnd, I've identified significant architectural differences that explain our environment loading issues and provide a clear path forward for fixing them.

## Reference SDK Architecture (katorymnd/pawa-pay-integration)

### Key Design Principles

1. **Clean Separation of Concerns**: The SDK doesn't handle environment variables internally
2. **Simple Constructor Pattern**: API token passed directly to constructor
3. **External Environment Management**: Uses `phpdotenv` for environment loading at application level
4. **Minimal Configuration**: Only 2 environment variables needed

### Environment Variable Strategy

**Reference SDK Environment Variables:**
```bash
PAWAPAY_SANDBOX_API_TOKEN=your_sandbox_token
PAWAPAY_PRODUCTION_API_TOKEN=your_production_token
```

**Reference SDK Usage Pattern:**
```php
// Load environment variables using phpdotenv
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Select token based on environment
$environment = getenv('ENVIRONMENT') ?: 'sandbox';
$apiTokenKey = 'PAWAPAY_' . strtoupper($environment) . '_API_TOKEN';
$apiToken = $_ENV[$apiTokenKey] ?? null;

// Pass token to SDK
$pawaPayClient = new ApiClient($apiToken, $environment, $sslVerify, $apiVersion);
```

### Dependencies Used
- `vlucas/phpdotenv`: ^5.6 (for environment loading)
- `guzzlehttp/guzzle`: ^7.9 (HTTP client)
- `symfony/validator`: ^7.1 (input validation)
- `monolog/monolog`: ^3.7 (logging)

## Our Current Implementation Issues

### Problems Identified

1. **Over-engineered Environment Loading**: Our `src/Support/env.php` tries to handle environment loading internally within the SDK
2. **Path Resolution Issues**: `__DIR__` magic constant fails when loaded through Composer autoloader
3. **Complex Configuration**: 20+ environment variables vs. reference SDK's 2
4. **Mixed Responsibilities**: SDK handles both environment loading and API operations

### Current Architecture Problems

**Our current approach:**
```php
// Problematic - tries to load .env internally
Myzuwa\PawaPay\Support\env('PAWAPAY_API_TOKEN', 'NOT_FOUND')
```

**Should be:**
```php
// Clean approach - token passed from application
new ApiClient($apiToken, $environment)
```

## Recommended Solutions

### Option 1: Align with Reference SDK (Recommended)

**Restructure our SDK to match the reference implementation:**

1. **Simplify Environment Variables** - Use only what's needed:
   ```bash
   PAWAPAY_SANDBOX_API_TOKEN=your_sandbox_token
   PAWAPAY_PRODUCTION_API_TOKEN=your_production_token
   ```

2. **Use phpdotenv for Environment Loading**:
   ```bash
   composer require vlucas/phpdotenv
   ```

3. **Modify SDK Constructor** to accept token directly:
   ```php
   // Instead of loading internally
   $pawaPayClient = new ApiClient($apiToken, $environment);
   ```

4. **Handle Environment Loading at Application Level**:
   ```php
   // In application bootstrap (e.g., config/bootstrap.php)
   $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
   $dotenv->load();

   $environment = getenv('PAWAPAY_ENVIRONMENT') ?: 'sandbox';
   $apiTokenKey = 'PAWAPAY_' . strtoupper($environment) . '_API_TOKEN';
   $apiToken = getenv($apiTokenKey);
   ```

### Option 2: Fix Current Path Resolution Issue

**If we must keep current architecture, fix the path issue:**

```php
// In src/Support/env.php - loadEnvFile() function
function loadEnvFile()
{
    // Fix path resolution for autoloader compatibility
    $envFile = dirname(__DIR__, 2) . '/.env';

    // Alternative: Use more robust path detection
    if (!file_exists($envFile)) {
        $envFile = getcwd() . '/.env';
    }

    // ... rest of function
}
```

### Option 3: Hybrid Approach

**Keep environment loading in SDK but make it more robust:**

1. **Add fallback mechanisms** for path resolution
2. **Use multiple detection methods**:
   - `dirname(__DIR__, 2)`
   - `getcwd()`
   - `$_SERVER['DOCUMENT_ROOT']`
3. **Add debugging capabilities** to help troubleshoot path issues

## Implementation Plan

### Phase 1: Immediate Fix (Resolve Current Issue)

**Fix the path resolution issue in current implementation:**

1. **Update `src/Support/env.php`**:
   ```php
   function loadEnvFile()
   {
       // Try multiple path resolution methods
       $possiblePaths = [
           dirname(__DIR__, 2) . '/.env',           // Project root
           getcwd() . '/.env',                      // Current working directory
           $_SERVER['DOCUMENT_ROOT'] . '/../.env',  // Web root relative
       ];

       foreach ($possiblePaths as $envFile) {
           if (file_exists($envFile)) {
               // Load from this file
               break;
           }
       }
   }
   ```

### Phase 2: Long-term Architecture Improvement

**Align with reference SDK best practices:**

1. **Add phpdotenv dependency**
2. **Simplify environment variable structure**
3. **Refactor SDK to accept tokens directly**
4. **Move environment loading to application level**

## Benefits of Reference SDK Approach

### Advantages

1. **Simplicity**: Only 2 environment variables vs. our 20+
2. **Reliability**: No path resolution issues
3. **Flexibility**: Easy to switch between sandbox/production
4. **Maintainability**: Clear separation of concerns
5. **Industry Standard**: Uses established libraries (phpdotenv, guzzlehttp)

### Comparison Table

| Aspect | Reference SDK | Our Current SDK |
|--------|---------------|-----------------|
| Environment Variables | 2 (sandbox + production tokens) | 20+ variables |
| Dependencies | Established libraries | Custom implementation |
| Path Resolution | None (external loading) | Complex `__DIR__` issues |
| Constructor | Simple: `new ApiClient($token, $env)` | Complex internal loading |
| Error Handling | Clear and predictable | Path-dependent failures |

## Immediate Action Required

### Priority 1: Fix Current Environment Loading

The immediate issue blocking our PawaPay integration is the path resolution problem. We need to fix the `loadEnvFile()` function to work reliably with the Composer autoloader.

### Priority 2: Consider Architecture Simplification

Long-term, we should consider aligning our SDK architecture with the reference implementation for better maintainability and reliability.

## Files to Modify

1. **`src/Support/env.php`**: Fix path resolution in `loadEnvFile()` function
2. **`composer.json`**: Consider adding `vlucas/phpdotenv` dependency
3. **`config/bootstrap.php`**: Potentially handle environment loading at application level

## Testing Strategy

1. **Test current fix**: Verify environment loading works with autoloader
2. **Test backward compatibility**: Ensure existing functionality still works
3. **Test edge cases**: Different server environments, path structures
4. **Performance testing**: Ensure no degradation in load times

## Conclusion

The reference SDK provides an excellent model for how PawaPay integrations should be structured. Our current approach of handling environment loading within the SDK itself is overly complex and prone to path resolution issues. By aligning with their simpler, more robust architecture, we can resolve our immediate issues and build a more maintainable long-term solution.

The immediate fix should focus on resolving the path resolution issue, while long-term planning should consider adopting their cleaner architecture patterns.
