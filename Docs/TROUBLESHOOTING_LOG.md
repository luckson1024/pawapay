# Environment Variable Loading Troubleshooting Log

## 1. Problem Summary

The core issue is that the application fails to load critical API credentials, specifically the `PAWAPAY_API_TOKEN`, from the `.env` file when using the namespaced function `Myzuwa\PawaPay\Support\env()`. This prevents any interaction with the PawaPay API.

The API token is **supposed to be loaded** automatically from the `.env` file located at the project root. A helper function, `env()`, is designed to read this file, parse the key-value pairs, and make them available to the application.

However, this mechanism is failing. When the `env()` function is called through the Composer autoloader, it returns `NOT_FOUND`, even though a separate test script (`test_env_loading.php`) can successfully read the same file.

## 2. What Has Been Tried

So far, we have attempted the following fixes, none of which have resolved the issue:

*   **Path Resolution Fix**: Corrected the file path in `src/Support/env.php` to ensure it correctly pointed to the project root.
*   **Forced Reload**: Removed the static cache from the `env()` function to ensure the `.env` file was read on every call.
*   **Replaced with Standard Library**: Replaced the entire custom file loading logic with the industry-standard `vlucas/phpdotenv` library.
*   **Autoloader Regeneration**: Ran `composer dump-autoload --optimize` multiple times to ensure the autoloader was up-to-date.

Despite these efforts, the command `php -r "require_once 'vendor/autoload.php'; echo 'PAWAPAY_API_TOKEN: ' . Myzuwa\PawaPay\Support\env('PAWAPAY_API_TOKEN', 'NOT_FOUND') . PHP_EOL;"` consistently returns `NOT_FOUND`.

## 3. Next Steps

The persistence of this issue suggests a deeper conflict within the application's bootstrap process. The next phase of troubleshooting will focus on two key areas:

1.  **Analyze the Working Script**: I will now thoroughly examine the `test_env_loading.php` script to identify what it does differently. There may be a subtle but critical difference in how it initializes the environment.
2.  **Search for Documentation**: I will search for any official PawaPay integration documentation that might provide guidance on the recommended way to load environment variables. There may be a specific setup or configuration that we are missing.