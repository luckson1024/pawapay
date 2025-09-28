# PawaPay Payment Page Guide

## Overview
The PawaPay Payment Page allows merchants to request payments from customers through a mobile-optimized interface. This guide explains how to integrate and use the payment page for secure mobile money payments.

## Prerequisites
Before implementing the Payment Page, ensure you have:
- API tokens configured
- Callbacks set up for payment notifications
- Understanding of mobile money considerations
 
## Features
- **User-Friendly Experience**: Optimized for mobile money payments
- **Responsive Design**: Works on desktop and mobile devices
- **Low-Code Integration**: Supports all countries and providers
- **Use Cases**: Suitable for both e-commerce and e-wallet scenarios
- **Validation**: Real-time phone number validation and provider prediction
- **Limits**: Automatic validation of transaction limits
- **Availability**: Instant support for new countries/providers
- **Downtime Handling**: Integrated provider status information

## Payment Flow
The payment process consists of three main steps:
1. Enter payment details
2. Authorize the payment
3. Confirmation

## API Usage

### Basic Payment Page (All Countries)
Create a payment page that allows customers to select their country and enter amount:

```http
POST https://api.sandbox.pawapay.io/v2/paymentpage

{
    "depositId": "695776cf-73ba-42ff-b9cb-2b9acc008e22",
    "returnUrl": "https://merchant.com/returnUrl",
    "reason": "Demo payment"
}
```

### Fixed Phone Number
For registered users with predetermined phone numbers:

```http
POST https://api.sandbox.pawapay.io/v2/paymentpage

{
    "depositId": "375fb9c9-fe34-48fd-95b2-b0aff9928673",
    "returnUrl": "https://merchant.com/returnUrl",
    "msisdn": "233593456789",
    "reason": "Demo payment"
}
```

### Fixed Amount
When the payment amount is predetermined but customers choose their phone number:

```http
POST https://api.sandbox.pawapay.io/v2/paymentpage

{
    "depositId": "375fb9c9-fe34-48fd-95b2-b0aff9928673",
    "returnUrl": "https://merchant.com/returnUrl",
    "amount": "100",
    "country": "GHA",
    "reason": "Demo payment"
}
```

### Fixed Amount and Phone Number
For precise control over payment parameters:

```http
POST https://api.sandbox.pawapay.io/v2/paymentpage

{
    "depositId": "695776cf-73ba-42ff-b9cb-2b9acc008e22",
    "returnUrl": "https://merchant.com/returnUrl",
    "msisdn": "233593456789",
    "amount": "100",
    "reason": "Demo payment"
}
```

## Redirect Response
After successful initiation, you'll receive a redirectUrl to redirect your customer to:

```json
{
   "redirectUrl": "https://sandbox.paywith.pawapay.io/?token=..."
}
```

## Payment Status Handling

### Status Checking
Payments can be tracked in real-time through callbacks or polling:

- Use the `check deposit status` endpoint if callbacks aren't configured
- Validate final status on your return URL
- Session expires after 15 minutes if not completed

### Handling Results
- **COMPLETED**: Payment successful
- **FAILED**: Payment failed (check failureReason)
- **PROCESSING**: Payment in progress
- **IN_RECONCILIATION**: Under automatic reconciliation

### Failure Handling
Standardized failure codes and messages. Key codes include:
- `UNSPECIFIED_FAILURE`: Provider indicated failure without details
- `UNKNOWN_ERROR`: General system error
- Implement retry logic with new depositId for failed payments

## Ensuring Consistency

### Best Practices
1. **Defensive Status Handling**: Always validate against all possible statuses
2. **Network Error Recovery**: Use depositId to recover from communication failures
3. **Automated Reconciliation**: Implement periodic status checks

### Pseudo-code Example
```javascript
// Store depositId before initiation
myInvoice.setExternalPaymentId(depositId).save();

try {
    var initiationResponse = pawaPay.initiateDeposit(depositId, ...);
} catch (InterruptedException e) {
    var checkResult = pawaPay.checkDepositStatus(depositId);

    if (result.status === "FOUND") {
        // Handle based on payment status
    } else if (result.status === "NOT_FOUND") {
        myInvoice.setPaymentStatus(FAILED);
    } else {
        // Leave as pending for reconciliation cycle
    }
}
```

### Reconciliation Cycle
Implement automated checks for pending payments older than 15 minutes:

```javascript
// Run every few minutes
pendingInvoices = invoices.getAllPendingForLongerThan15Minutes();

for (invoice in pendingInvoices) {
    checkResult = pawaPay.checkDepositStatus(invoice.getExternalPaymentId());

    if (checkResult.status === "FOUND") {
        handleInvoiceStatus(checkResult.data);
    } else if (checkResult.status === "NOT_FOUND") {
        invoice.setPaymentStatus(FAILED);
    }
    // Continue processing for other statuses
}
```

## Testing
Use sandbox environment with test phone numbers to simulate various scenarios:
- Test different failure codes
- Verify error handling
- Validate payment flows

## Security
- Consider implementing additional security layers
- Store sensitive payment data securely
- Validate all input parameters

## Going Live
- Ensure proper configuration for production environment
- Set up proper monitoring and alerting
- Test thoroughly before deployment
