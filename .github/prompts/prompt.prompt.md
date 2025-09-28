---
mode: agent
---
You are a senior QA automation engineer responsible for testing the PawaPay SDK integration inside the Modesy (Myzuwa) payment system.

Context:
- The integration folder has been renamed from `pawapay-v2-integration` to `pawapay`.
- Old documentation is available in the `Docs` folder, but it must be aligned with the new integration goal.
- The SDK must be tested end-to-end with real simulation data, including page interactions, API calls, and webhook responses.
- Modesy uses a centralized `handlePayment()` flow with vendor commissions, multi-vendor cart support, wallet deposits, service plans, and promotion fees.

Your Task:
1. Write a **test script framework** that:
   - Loads checkout sessions with sample data.
   - Sends simulated payment initiation requests to the PawaPay SDK.
   - Receives webhook callbacks and verifies payload signatures.
   - Passes transaction objects into Modesy’s `handlePayment()` to trigger order creation, commission distribution, and wallet/service logic.
   - Logs and validates state changes (order status, vendor commissions, wallet balances).

2. Cover the following **test cases**:
   - Single product checkout
   - Multi-vendor checkout with different commission rates
   - Membership subscription payments
   - Wallet deposits (user top-ups)
   - Featured product promotions
   - Error scenarios: amount mismatch, currency mismatch, duplicate payments, webhook replay

3. Ensure the test framework:
   - Runs simulations that mimic **real page interactions** (checkout initiation → SDK redirect → webhook → order creation).
   - Uses data-driven test inputs for multiple currencies, vendors, and payment types.
   - Aligns outputs with existing documentation in `Docs`, updating inconsistencies automatically.

Deliverables:
- A modular PHP test script located in `/pawapay/tests/` (e.g., `PawaPaySdkTest.php`).
- Documentation updates in `/Docs/` aligned with actual test flow.
- Logs and reports showing SDK → Modesy transaction lifecycle verification.



🎯 Task Definition: PawaPay SDK & Myzuwa (Modesy) Integration Testing
Objective

Fully test the PawaPay SDK integration with the Myzuwa (Modesy) payment system to ensure correct handling of all payment flows, webhook events, and Modesy’s internal commission/order lifecycle.

Requirements
Functional Requirements

Test Coverage

✅ Single Product Checkout (physical/digital)

✅ Multi-Vendor Cart with commission splits

✅ Membership Subscriptions (vendor plans)

✅ Wallet Deposits (user top-up)

✅ Featured Product Promotions

✅ Error Scenarios:

Amount mismatch

Currency mismatch

Duplicate payments

Webhook replay / race conditions

Payment Flow Validation

Simulate checkout initiation with checkout_token.

Pass through PawaPay SDK request/response cycle.

Process incoming webhook callbacks with signature verification.

Feed transactions into handlePayment() and validate:

Order creation

Commission calculation (vendor/category/global)

Inventory update

Wallet/service balance updates

Documentation Alignment

Update existing docs in /Docs/ to match new SDK test flows.

Document expected vs actual test outputs.

Non-Functional Requirements

Automation: Test framework must run automatically (PHPUnit or raw runner).

Isolation: Tests run in sandbox mode, no production data impact.

Reproducibility: Same inputs must yield same outputs on multiple runs.

Logging: All requests, responses, and lifecycle states logged.

Maintainability: Modular test scripts located in /pawapay/tests/.

Constraints

The folder structure has changed: pawapay-v2-integration → pawapay.

Must integrate directly with Modesy’s centralized handlePayment() function.

Webhook route requires CSRF bypass.

Tests must simulate real page interaction flows (not just API unit calls).

Local currency and multi-vendor commission logic must be respected.

Success Criteria

✅ All test scenarios pass without breaking existing Modesy payment architecture.
✅ Transactions correctly move from initiation → SDK → webhook → order lifecycle.
✅ Commissions, wallet balances, and order states update accurately.
✅ Error handling is verified (e.g., mismatches, retries, duplicates).
✅ Updated /Docs/ reflect actual integration and test flows.
✅ Test scripts (/pawapay/tests/PawaPaySdkTest.php) can be executed repeatably.