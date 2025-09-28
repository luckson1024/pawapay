# PawaPay Integration TODO List for Myzuwa

This document tracks the phased implementation plan for integrating the PawaPay payment gateway into the Myzuwa e-commerce system.

Check Vital: pawapay-v2-integration\Docs\myzuwa_payment_analysis.md

## Core Principles

1.  **Non-Invasive Implementation:**
    *   Do not modify core Modesy files directly.
    *   All new code, configurations, and instructions must be documented in Markdown (`.md`) files within the `docs/` directory. This ensures that the live system can be updated safely without breaking existing functionality.

2.  **Professional Configuration Management:**
    *   All sensitive credentials (API tokens, secrets) and environment-specific settings (callback URLs) must be stored in a `.env` file.
    *   The application code must read these values using `$_ENV['VARIABLE_NAME']` to avoid hardcoding and to allow for easy configuration changes between sandbox and production environments.

3.  **Error-Free & Secure Execution:**
    *   All code must be syntactically correct and robust.
    *   **Crucially, all incoming callbacks from PawaPay must be cryptographically verified** using the `X-PawaPay-Signature` header to prevent fraudulent transaction updates.
    *   Implement idempotency by checking the `depositId` to prevent duplicate transaction processing.

4.  **Comprehensive Coverage & User Experience:**
    *   The integration must support all key payment flows: checkout, membership plans, and promotions.
    *   The user interface should be intuitive, allowing customers to easily select PawaPay and enter their phone number.
    *   The system should dynamically handle different mobile network operators (MNOs) based on PawaPay's API, rather than using hardcoded values.

## Phased Implementation Plan

### Phase 1: Collections (Deposits) - In Progress

*   [x] **Local SDK Testing:** Validated the `pawa-pay-integration` SDK for deposit initiation and status verification.
*   [x] **Database Setup:** Documented the SQL required to add PawaPay to the `payment_gateways` table.
*   [x] **Core Integration Files:**
    *   Created the [`app/Libraries/PawaPay.php`](app/Libraries/PawaPay.php) library, encapsulating all interaction with the PawaPay SDK.
    *   Added `pawapayPaymentPost` and `pawapayCallback` methods to [`app/Controllers/CartController.php`](app/Controllers/CartController.php).
    *   Created the payment view [`app/Views/cart/payment_methods/_pawapay.php`](app/Views/cart/payment_methods/_pawapay.php).
    *   Configured routes in [`app/Config/RoutesStatic.php`](app/Config/RoutesStatic.php) and excluded the callback from CSRF in [`app/Config/Filters.php`](app/Config/Filters.php).
*   [ ] **Next Steps:**
    1.  [x] **Dynamic MNO Handling:** Implement logic to fetch and display available mobile money operators to the user.
    2.  [x] **Wallet & Promotions:** Extend the integration to handle wallet top-ups and promotional payments.
    3.  [-] **End-to-End Testing:** Perform a complete test of the deposit flow in the sandbox environment.

### Phase 2: Payouts & Refunds

*   [ ] **Payouts:** Implement functionality for vendor withdrawals, including single and bulk payouts if supported.
*   [ ] **Refunds:** Implement the logic to process refunds for completed transactions via the PawaPay API.

### Phase 3: Finalization & Documentation

*   [x] **Create Final Documentation:** Consolidate all implementation steps, code snippets, and setup instructions into a final, comprehensive guide.
*   [ ] **Review and Refine:** Ensure all code adheres to best practices and that the documentation is clear and easy to follow for deployment on `myzuwa.com`.

## PawaPay Testing Plan

This plan outlines the step-by-step process for testing the PawaPay integration in Myzuwa, organized by feature phases.

### Phase 1: Collections (Deposits) Testing

This phase focuses on thoroughly testing the deposit flow for product purchases, memberships, and promotions.

#### Prerequisites:
*   PawaPay sandbox account with API keys (public_key, secret_key) configured in Myzuwa's `payment_gateways` table and `.env` file.
*   PawaPay callback URL configured in the PawaPay Dashboard to point to `[YOUR_MYZUWA_BASE_URL]/pawapay-callback`.
*   Access to a tool for simulating HTTP POST requests (e.g., Postman, Insomnia, or a simple script).
*   Myzuwa development environment running and accessible.

#### Test Cases:

**1. API Connectivity & MNO Fetching:**
    *   **Objective:** Verify Myzuwa can connect to PawaPay API and fetch MNOs.
    *   **Steps:**
        1.  Navigate to the Myzuwa payment method selection page.
        2.  Select PawaPay as the payment method.
        3.  Observe the "Mobile Network Operator" dropdown.
        4.  **Expected Result:** The dropdown should be populated with a list of MNOs. If empty, check Myzuwa logs for errors from `PawaPay::getMNOs()`.

**2. Product Purchase Deposit (End-to-End):**
    *   **Objective:** Verify a successful product purchase using PawaPay.
    *   **Steps:**
        1.  Add a product to the cart.
        2.  Proceed to checkout and select PawaPay.
        3.  Enter a valid phone number and select an MNO.
        4.  Initiate payment.
        5.  **Expected Result:** User is redirected to the PawaPay authorization page.
        6.  Complete the payment authorization on the PawaPay sandbox.
        7.  **Expected Result:** User is redirected back to Myzuwa's order completion page.
        8.  Verify the order status in Myzuwa's admin panel is "received".
        9.  Check Myzuwa's database: `orders` table should show the order with `payment_status = 'received'` and `payment_id` matching the PawaPay `depositId`.

**3. Membership Plan Deposit (End-to-End):**
    *   **Objective:** Verify a successful membership purchase using PawaPay.
    *   **Steps:**
        1.  Navigate to a membership plan purchase page.
        2.  Select PawaPay.
        3.  Enter a valid phone number and select an MNO.
        4.  Initiate payment.
        5.  **Expected Result:** User is redirected to the PawaPay authorization page.
        6.  Complete the payment authorization on the PawaPay sandbox.
        7.  **Expected Result:** User is redirected back to Myzuwa's membership activation page.
        8.  Verify the user's membership status is active in Myzuwa's admin panel.
        9.  Check Myzuwa's database: `membership_payments` table should show the payment as successful.

**4. Promotion Payment Deposit (End-to-End):**
    *   **Objective:** Verify a successful promotion purchase using PawaPay.
    *   **Steps:**
        1.  Navigate to a product promotion page.
        2.  Select PawaPay.
        3.  Enter a valid phone number and select an MNO.
        4.  Initiate payment.
        5.  **Expected Result:** User is redirected to the PawaPay authorization page.
        6.  Complete the payment authorization on the PawaPay sandbox.
        7.  **Expected Result:** User is redirected back to Myzuwa's promotion confirmation page.
        8.  Verify the product promotion is active in Myzuwa's admin panel.
        9.  Check Myzuwa's database: `promotions` table should show the promotion as active.

**5. Callback Signature Validation (Security Test):**
    *   **Objective:** Verify that Myzuwa correctly validates PawaPay callback signatures.
    *   **Steps:**
        1.  Initiate a successful deposit transaction (e.g., product purchase) in the sandbox.
        2.  Intercept or capture the PawaPay callback payload and the `X-PawaPay-Signature` header.
        3.  Using a tool (e.g., Postman), send a POST request to `[YOUR_MYZUWA_BASE_URL]/pawapay-callback` with the captured payload and a **valid** signature.
        4.  **Expected Result:** Myzuwa processes the callback successfully (HTTP 200), and the order status is updated.
        5.  Repeat step 3, but this time, intentionally **alter the signature** (e.g., change one character).
        6.  **Expected Result:** Myzuwa responds with `HTTP/1.1 400 Bad Request` and logs an "Invalid signature" error.

**6. Failed Deposit Scenario:**
    *   **Objective:** Verify Myzuwa handles failed PawaPay deposits gracefully.
    *   **Steps:**
        1.  Initiate a deposit with intentionally incorrect details (e.g., an invalid phone number if PawaPay sandbox allows, or simulate a failure on the PawaPay side).
        2.  **Expected Result:** PawaPay returns a failure, and Myzuwa displays an appropriate error message to the user.
        3.  Verify that no order is created or that the order status is `failed` in Myzuwa's database.

**7. Idempotency Test:**
    *   **Objective:** Ensure duplicate `initiateDeposit` requests are handled correctly by PawaPay.
    *   **Steps:**
        1.  Initiate a deposit. Before the callback is received, attempt to re-send the *exact same* `initiateDeposit` request (with the same `depositId`).
        2.  **Expected Result:** PawaPay should process the transaction only once, and Myzuwa should not create duplicate orders or process the payment twice.

### Phase 2: Payouts & Refunds Testing (Once Implemented)

This phase will be detailed once the Payouts and Refunds functionalities are implemented.

### Phase 3: Finalization & Documentation Review

*   **Objective:** Ensure all documentation is complete, accurate, and easy to follow.
*   **Steps:**
    1.  Review [`docs/pawapay_documentation.md`](docs/pawapay_documentation.md), [`docs/lineCode.md`](docs/lineCode.md), [`docs/pawapay_readiness.md`](docs/pawapay_readiness.md), and [`docs/todo.md`](docs/todo.md) for clarity, completeness, and accuracy.
    2.  Verify all code comments and inline documentation are up-to-date.
    3.  Confirm that all sensitive information (API keys, secrets) is correctly referenced from `.env` and not hardcoded.