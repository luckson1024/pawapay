# PawaPay v2 Integration Master Plan for Myzuwa.com

**Version:** 1.0
**Date:** 2025-09-19

## 1.0. Overview

This document outlines the phased development plan for integrating the successfully tested PawaPay v2 SDK into the live Myzuwa.com application. The strategy is to merge the code from the `pawapay-v2-integration` test folder into the Modesy framework, test each component thoroughly, and prepare for a production launch.

**Core Principles:**
- **Phased Rollout:** Implement one feature at a time (Deposits, Webhooks, Payouts) to minimize risk.
- **Security First:** All sensitive operations will be handled on the backend. Webhook validation is mandatory.
- **Test at Every Stage:** Each phase concludes with a specific test to validate the implementation before proceeding.

---

## 2.0. Phase 0: Environment Preparation & Setup

**Goal:** Prepare the Myzuwa.com codebase and database for the new integration without yet adding the core logic.

- [ ] **2.1. Git Branching:**
    - From your `main` or `develop` branch, create a new feature branch to contain all upcoming changes.
    - **Command:** `git checkout -b feature/pawapay-v2-integration`

- [ ] **2.2. Configuration:**
    - **Database:** In your Myzuwa.com admin panel, navigate to Payment Settings. Find the "PawaPay" gateway and ensure the following fields are set with your **Sandbox** credentials:
        - `Public Key`: Your PawaPay API Token.
        - `Secret Key`: Your PawaPay API Secret (for webhook signature verification).
        - `Environment`: `Sandbox`.
    - **File System:** Ensure the directory `app/Libraries/` exists and is writable.

- [ ] **2.3. Database Migration:**
    - The integration requires a `pending_payments` table to track transactions that have been initiated but not yet confirmed by a webhook.
    - Connect to your Myzuwa.com database and execute the following SQL `CREATE TABLE` statement:
    ```sql
    CREATE TABLE `pending_payments` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `deposit_id` VARCHAR(255) NOT NULL UNIQUE,
      `payment_token` VARCHAR(255) NOT NULL,
      `payment_type` VARCHAR(50) NOT NULL,
      `currency` VARCHAR(10) NOT NULL,
      `payment_amount` DECIMAL(10, 2) NOT NULL,
      `net_amount` DECIMAL(10, 2) NOT NULL,
      `fee` DECIMAL(10, 2) NOT NULL,
      `created_at` DATETIME NOT NULL,
      INDEX `payment_token_index` (`payment_token`)
    );
    ```

---

## 3.0. Phase 1: Core Integration (Payment Initiation)

**Goal:** Merge the tested SDK and controller logic into the Modesy framework to allow users to initiate a payment from the checkout page.

- [ ] **3.1. Copy the PawaPay Library:**
    - Copy the file `pawapay-v2-integration/app/Libraries/PawaPay.php`.
    - Paste it into your main application at `myzuwa-codebase/app/Libraries/PawaPay.php`.

- [ ] **3.2. Merge the `CartController` Logic:**
    - **Do not overwrite the entire file.** Open your main application's `myzuwa-codebase/app/Controllers/CartController.php`.
    - Open the `pawapay-v2-integration/app/Controllers/CartController.php` file from your test project.
    - Carefully copy the entire `pawapayPaymentPost()` function from the test file and paste it inside the main application's `CartController` class.
    - At the top of the main `CartController.php` file, ensure the `PawaPay` library is imported by adding `use App\Libraries\PawaPay;`.

- [ ] **3.3. Add the Payment Route:**
    - Open `myzuwa-codebase/app/Config/RoutesStatic.php` (or your main routing file).
    - Add the route that connects a browser request to your new controller function.
    ```php
    $routes->post('payment/pawapay-post', 'CartController::pawapayPaymentPost');
    ```
    *(Note: Using `-post` suffix is a good practice to distinguish from GET routes).*

- [ ] **3.4. Update the Frontend View:**
    - Open the file `myzuwa-codebase/app/Views/cart/payment_methods/_pawapay.php`.
    - This file displays the payment option on the checkout page. Replace its contents with the code that includes the phone number input and the necessary JavaScript.
    - **Action:** Paste the full contents of the `_pawapay.php` file from the `PLAN.md` provided earlier (the one with the HTML form and `fetch` JavaScript).
    - **Crucial:** In the JavaScript, find the `fetch` URL and ensure it matches the route you defined in step 3.3: `fetch('<?php echo langBaseUrl(); ?>/payment/pawapay-post', ...)`

- [ ] **3.5. Phase 1 Testing:**
    - **Action:** Deploy your `feature/pawapay-v2-integration` branch to a staging server or test locally.
    - **Test Case:** Go through the checkout process, select PawaPay, enter a valid test phone number (`260763456789`), and click the final "Pay" button.
    - **Expected Outcome:** The page should display the "Payment initiated. Please check your phone to approve" message. The transaction should appear in your PawaPay sandbox dashboard. Your `pending_payments` table in the database should contain a new row for this transaction.

---

## 4.0. Phase 2: Webhook Implementation and Automation

**Goal:** Implement the webhook handler to automatically process PawaPay's final status notifications, turning a "pending" payment into a completed order.

- [ ] **4.1. Add the Webhook Route:**
    - Open `myzuwa-codebase/app/Config/RoutesStatic.php`.
    - Add the new public route for PawaPay to call. It's crucial to add this route to the CSRF exclusion list in your framework's `Filters.php` config.
    ```php
    $routes->post('webhook/pawapay', 'WebhookController::pawapay');
    ```

- [ ] **4.2. Create the `WebhookController`:**
    - Create a new controller file at `myzuwa-codebase/app/Controllers/WebhookController.php`.
    - **Action:** Paste the full `pawapayWebhook` controller code from the `PLAN.md` provided earlier. This code includes signature validation, JSON parsing, and the logic to find the pending payment and process the order.
    - **Critical Integration Point:** Inside the `if ($data['status'] == 'COMPLETED')` block, you must find and call the **correct Modesy function that finalizes an order**. It will look something like `$this->order_model->update_order_payment_received($pendingPayment->payment_token);`. This is the most important step for the integration to work.

- [ ] **4.3. Phase 2 Testing (using Ngrok):**
    - **Action:** Run Ngrok pointed at your local development server (`ngrok http 80`).
    - **Configuration:** Update your PawaPay Sandbox dashboard webhook URL to your Ngrok public URL: `https://<your-id>.ngrok-free.app/webhook/pawapay`.
    - **Test Case:** Perform a complete payment flow from Phase 1. Use the success test number `260763456789`.
    - **Expected Outcome:**
        1. The payment is initiated.
        2. A few seconds later, the webhook is received.
        3. The corresponding record in your `pending_payments` table is **deleted**.
        4. The order status in your main `orders` table is updated to "Payment Received" (or your equivalent).

---

## 5.0. Phase 3: Payouts, Refunds, and Production Testing

**Goal:** Implement marketplace features and conduct final testing before go-live.

- [ ] **5.1. Implement Payouts & Refunds:**
    - Follow the established pattern:
        - Extend the `PawaPay.php` library with `initiatePayout` and `requestRefund` methods.
        - Create new routes and controller functions (likely in an `AdminController`).
        - Build the necessary UI in the admin panel.
        - Extend the `WebhookController` to handle `payoutId` and `refundId` notifications.

- [ ] **5.2. Full Staging Environment Test:**
    - **Action:** Deploy the complete, feature-rich branch to a dedicated staging server.
    - **Configuration:** Ensure the staging server is using the **Sandbox** API keys.
    - **User Acceptance Testing (UAT):**
        - [ ] Test a successful deposit.
        - [ ] Test a failed deposit (e.g., insufficient funds).
        - [ ] Test a successful payout from the admin panel.
        - [ ] Test a successful refund from the admin panel.
        - [ ] Verify all database records are updated correctly for every scenario.
        - [ ] Verify email notifications (if any) are sent correctly.

- [ ] **5.3. Final Go-Live Checklist:**
    - [ ] **Obtain Production Keys:** Get your live API Token and Secret from PawaPay.
    - [ ] **Code Review:** Perform a final review of the entire `feature/pawapay-v2-integration` branch, focusing on security and error handling.
    - [ ] **Merge to Production Branch:** Merge the feature branch into your `main` branch.
    - [ ] **Deploy to Production:** Deploy the final code to Myzuwa.com.
    - [ ] **Update Production Configuration:** In your live admin panel, update the PawaPay settings with the **Production** keys and set the environment to "Production".
    - [ ] **Update Production Webhook:** In your PawaPay **Production** dashboard, set the webhook URL to your live domain: `https://myzuwa.com/webhook/pawapay`.
    - [ ] **Final Live Test:** Conduct one final end-to-end test with a real, small-value transaction to ensure everything is functioning as expected in the live environment.
    - [ ] **Monitor:** Closely monitor server logs and payment statuses for the first 24 hours after launch.