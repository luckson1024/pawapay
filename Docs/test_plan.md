# PawaPay SDK Test Plan

## 0. A Shift in Testing Strategy

This document outlines a new strategy for testing the PawaPay SDK. The previous approach relied heavily on manual testing scripts, which are slow, error-prone, and not scalable.

**The new strategy is to focus on building a comprehensive suite of automated tests that can be run on every code change.** This will allow us to:

*   Test the SDK functionality in a reliable and repeatable way.
*   Catch regressions early in the development process.
*   Move away from manual testing and free up developer time.

This plan details how we will achieve this by expanding our unit and integration tests, and by setting up a continuous integration (CI) pipeline.

## 1. Analysis of Existing Tests

An analysis of the `tests` directory reveals a mix of automated tests and manual testing scripts.

### 1.1. Automated Tests

The following directories contain automated tests using PHPUnit:

*   `tests/Unit`: Contains unit tests that mock dependencies and test individual components in isolation. The existing `PawaPayTest.php` is a good starting point but lacks comprehensive coverage.
*   `tests/Integration`: Contains integration tests that make real API calls to the PawaPay sandbox environment. The existing `PawaPayIntegrationTest.php` covers basic scenarios but needs to be expanded.

### 1.2. Manual Testing Scripts

A significant portion of the files in the `tests` directory are not automated tests but rather manual testing scripts. These include:

*   `test_api_connection.php`
*   `test_basic_api.php`
*   `test_config_loading.php`
*   `test_config.php`
*   `test_dashboard.php`
*   `test_env_loading.php`
*   `test_env_web.php`
*   `test_helpers.php`
*   `test_integration.php`
*   `test_mno_fetching.php`
*   `test_modesy_integration.php`
*   `test_payment.php`
*   `test_token.php`
*   `test_webhook.php`

These scripts are useful for debugging and manual verification but are not a substitute for a comprehensive suite of automated tests. They cannot be run as part of a continuous integration (CI) pipeline and do not provide reliable regression testing.

## 2. Proposed Test Plan

To improve the quality and reliability of the PawaPay SDK, I propose the following plan to enhance the automated test suite.

### 2.1. Expand Unit Tests

The goal of the unit tests is to test each component of the SDK in isolation. This will be achieved by:

*   **Expand `PawaPayTest.php`:**
    *   Add test cases for all public methods of the `PawaPay` class, including:
        *   `initiateDeposit()`
        *   `checkDepositStatus()`
        *   `initiatePayout()`
        *   `checkPayoutStatus()`
        *   `initiateRefund()`
        *   `getWalletBalances()`
        *   `getAvailableOperators()`
        *   `predictOperator()`
    *   Test edge cases and error conditions, such as:
        *   Invalid input data (e.g., incorrect amount, invalid phone number).
        *   Mocked API error responses (e.g., 4xx and 5xx errors).
        *   Network failures.
*   **Create New Unit Tests:**
    *   Create a new unit test file for the `WebhookHandler` class to test the webhook signature verification logic in isolation.
    *   Create a new unit test file for the `MNOService` class to test the mobile network operator logic.
    *   Create unit tests for any other classes in the `src` directory.

### 2.2. Expand Integration Tests

The goal of the integration tests is to test the SDK's interaction with the live PawaPay sandbox API. This will be achieved by:

*   **Expand `PawaPayIntegrationTest.php`:**
    *   Add integration tests for the full API lifecycle, including:
        *   Successful and failed deposit scenarios.
        *   Successful and failed payout scenarios.
        *   Successful and failed refund scenarios.
    *   Add tests for webhook notifications by using a tool like `ngrok` to expose a local endpoint to the internet and receive real webhook calls from the PawaPay sandbox.
*   **Test Data Management:**
    *   Implement a mechanism to automatically provision test data (e.g., create a new user, a new product, etc.) before running the tests. This will ensure that the tests are run in a consistent and predictable environment.

### 2.3. Test Execution and CI

*   **Test Runner:**
    *   Configure a script in `composer.json` to allow running all automated tests (both unit and integration) with a single command (e.g., `composer test`).
*   **Continuous Integration:**
    *   Set up a CI pipeline (e.g., using GitHub Actions) to automatically run the test suite on every push and pull request. This will ensure that any new changes do not break existing functionality.

### 2.4. Deprecate Manual Test Scripts

Once the automated test suite is comprehensive enough, the manual test scripts in the `tests` directory can be deprecated and eventually removed. This will help to streamline the development process and reduce confusion.