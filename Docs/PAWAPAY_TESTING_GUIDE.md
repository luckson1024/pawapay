# PawaPay Integration Testing Guide

## Overview

This comprehensive testing guide provides all the necessary information to test the PawaPay integration with Myzuwa.com. It includes test phone numbers, expected results, and testing procedures for different scenarios.

## Environment Configuration

### API Credentials
- **API Token**: `eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJ0dCI6IkFBVCIsInN1YiI6IjEwMDc5IiwibWF2IjoiMSIsImV4cCI6MjA3MzcyMjE5NywiaWF0IjoxNzU4MTg5Mzk3LCJwbSI6IkRBRixQQUYiLCJqdGkiOiIxOWVlMTVjZS0zNDcyLTQ4NDItODZhYi0yMTEzOTY0NzA2MDkifQ.5V41KcLlRau7rox91WulAbPce9cAITjIUTE05oWSxo6SsXIoi3C5EN_4eC8X2KqkrS32-HdmPxxA8luzEY2bgw`
- **Environment**: Sandbox
- **Base URL**: `https://api.sandbox.pawapay.io`

### Test Interface
Access the testing interface at: `http://localhost:8000/index.php` or http://localhost/pathDirector

## Mobile Network Operators

### Zambia (ZMB)
- **Airtel**: `AIRTEL_OAPI_ZMB`
- **MTN**: `MTN_MOMO_ZMB`
- **Zamtel**: `ZAMTEL_MOMO_ZMB`

## Test Phone Numbers and Expected Results

### Airtel (AIRTEL_OAPI_ZMB)

| Operation | MSISDN | Status | Failure Code |
|-----------|--------|--------|--------------|
| **Deposit** | 260973456019 | FAILED | PAYER_LIMIT_REACHED |
| **Deposit** | 260973456039 | FAILED | PAYMENT_NOT_APPROVED |
| **Deposit** | 260973456049 | FAILED | INSUFFICIENT_BALANCE |
| **Deposit** | 260973456069 | FAILED | UNSPECIFIED_FAILURE |
| **Deposit** | 260973456129 | SUBMITTED | - |
| **Deposit** | 260973456789 | COMPLETED | - |
| **Payout** | 260973456089 | FAILED | RECIPIENT_NOT_FOUND |
| **Payout** | 260973456119 | FAILED | UNSPECIFIED_FAILURE |
| **Payout** | 260973456129 | SUBMITTED | - |
| **Payout** | 260973456789 | COMPLETED | - |

### MTN (MTN_MOMO_ZMB)

| Operation | MSISDN | Status | Failure Code |
|-----------|--------|--------|--------------|
| **Deposit** | 260763456019 | FAILED | PAYER_LIMIT_REACHED |
| **Deposit** | 260763456029 | FAILED | PAYER_NOT_FOUND |
| **Deposit** | 260763456039 | FAILED | PAYMENT_NOT_APPROVED |
| **Deposit** | 260763456069 | FAILED | UNSPECIFIED_FAILURE |
| **Deposit** | 260763456129 | SUBMITTED | - |
| **Deposit** | 260763456789 | COMPLETED | - |
| **Payout** | 260763456079 | FAILED | RECIPIENT_NOT_FOUND |
| **Payout** | 260763456119 | FAILED | UNSPECIFIED_FAILURE |
| **Payout** | 260763456129 | SUBMITTED | - |
| **Payout** | 260763456789 | COMPLETED | - |

### Zamtel (ZAMTEL_MOMO_ZMB)

| Operation | MSISDN | Status | Failure Code |
|-----------|--------|--------|--------------|
| **Deposit** | 260953456704 | FAILED | INSUFFICIENT_BALANCE |
| **Deposit** | 260953456712 | FAILED | UNSPECIFIED_FAILURE |
| **Deposit** | 260953456789 | SUBMITTED | - |
| **Deposit** | 260953456700 | COMPLETED | - |
| **Payout** | 260953456712 | FAILED | UNSPECIFIED_FAILURE |
| **Payout** | 260953456789 | SUBMITTED | - |
| **Payout** | 260953456700 | COMPLETED | - |

## Testing Procedures

### 1. Basic Connectivity Test

**Objective**: Verify API connection and authentication

**Steps**:
1. Open the test interface: `http://localhost:8000/index.php`
2. Check that the page loads without errors
3. Verify that the operator dropdown is populated
4. Check that the configuration test link works

**Expected Results**:
- [ ] Page loads successfully
- [ ] Operator dropdown contains all three Zambian operators
- [ ] No authentication errors

### 2. Successful Deposit Test

**Objective**: Test successful payment initiation and completion

**Steps**:
1. Use phone number: `260973456789` (Airtel - COMPLETED)
2. Use phone number: `260763456789` (MTN - COMPLETED)
3. Use phone number: `260953456700` (Zamtel - COMPLETED)
4. Amount: 10.00 ZMW
5. Select corresponding operator

**Expected Results**:
- [ ] Payment initiated successfully
- [ ] Status: ACCEPTED (initial)
- [ ] Status: COMPLETED (final via webhook)

### 3. Failed Deposit Tests

**Objective**: Test various failure scenarios

#### Test Case 1: PAYER_LIMIT_REACHED
- Phone: `260973456019` (Airtel)
- Phone: `260763456019` (MTN)
- Expected: FAILED - PAYER_LIMIT_REACHED

#### Test Case 2: PAYMENT_NOT_APPROVED
- Phone: `260973456039` (Airtel)
- Phone: `260763456039` (MTN)
- Expected: FAILED - PAYMENT_NOT_APPROVED

#### Test Case 3: INSUFFICIENT_BALANCE
- Phone: `260973456049` (Airtel)
- Phone: `260953456704` (Zamtel)
- Expected: FAILED - INSUFFICIENT_BALANCE

#### Test Case 4: PAYER_NOT_FOUND
- Phone: `260763456029` (MTN)
- Expected: FAILED - PAYER_NOT_FOUND

#### Test Case 5: UNSPECIFIED_FAILURE
- Phone: `260973456069` (Airtel)
- Phone: `260763456069` (MTN)
- Phone: `260953456712` (Zamtel)
- Expected: FAILED - UNSPECIFIED_FAILURE

### 4. Webhook Testing

**Objective**: Verify webhook processing

**Steps**:
1. Monitor webhook logs: `logs/webhook_test.log`
2. Complete a successful payment
3. Check that webhook is received and processed
4. Verify order status updates

**Expected Results**:
- [ ] Webhook received with correct payload
- [ ] Signature validation passes
- [ ] Order status updated to "completed"

### 5. Operator Prediction Test

**Objective**: Test phone number validation and operator detection

**Steps**:
1. Use the operator prediction endpoint: `/cart/predict-operator?phone=260763456789`
2. Test with different phone number prefixes:
   - 26076xxxxx → MTN
   - 26097xxxxx → Airtel
   - 26095xxxxx → Zamtel

**Expected Results**:
- [ ] Correct operator prediction
- [ ] Valid phone number format
- [ ] Proper error handling for invalid numbers

## Test Amount Guidelines

### Valid Test Amounts
- **Minimum**: 1.00 ZMW
- **Maximum**: 20,000.00 ZMW
- **Recommended**: 10.00 ZMW (for most tests)

### Invalid Test Amounts
- **Too small**: 0.50 ZMW (should fail)
- **Too large**: 25,000.00 ZMW (should fail)
- **Invalid format**: "abc" (should fail)

## Error Code Reference

### Common Deposit Failure Codes
- `PAYER_LIMIT_REACHED`: Customer has exceeded transaction limits
- `PAYMENT_NOT_APPROVED`: Customer declined the payment
- `INSUFFICIENT_BALANCE`: Insufficient funds in customer account
- `PAYER_NOT_FOUND`: Customer phone number not found
- `UNSPECIFIED_FAILURE`: Generic provider error
- `INVALID_PHONE_NUMBER`: Malformed phone number
- `INVALID_CURRENCY`: Unsupported currency
- `INVALID_AMOUNT`: Amount outside allowed range
- `AMOUNT_OUT_OF_BOUNDS`: Amount too small or too large
- `PROVIDER_TEMPORARILY_UNAVAILABLE`: Provider system down

### Common Payout Failure Codes
- `RECIPIENT_NOT_FOUND`: Recipient phone number not found
- `UNSPECIFIED_FAILURE`: Generic provider error
- `INSUFFICIENT_BALANCE`: Insufficient merchant balance
- `INVALID_PHONE_NUMBER`: Malformed phone number

## Testing Checklist

### Pre-Test Setup
- [ ] Verify API credentials are configured
- [ ] Check database connectivity
- [ ] Ensure webhook endpoint is accessible
- [ ] Clear previous test logs

### Functional Tests
- [ ] Basic connectivity test
- [ ] Successful deposit test (all operators)
- [ ] Failed deposit tests (all scenarios)
- [ ] Webhook processing test
- [ ] Operator prediction test

### Edge Case Tests
- [ ] Invalid phone number format
- [ ] Amount too small/large
- [ ] Unsupported currency
- [ ] Network timeout simulation
- [ ] Invalid operator selection

### Security Tests
- [ ] Webhook signature validation
- [ ] Invalid signature rejection
- [ ] Request rate limiting
- [ ] Input sanitization

## Troubleshooting

### Common Issues

#### 1. "Class CartController not found"
**Solution**: Use the standalone test interface instead of the full Modesy framework

#### 2. "Invalid API credentials"
**Solution**: Verify the API token is correctly set in the .env file

#### 3. "Webhook not received"
**Solution**: Check that the webhook URL is accessible and not blocked by CSRF protection

#### 4. "Operator dropdown empty"
**Solution**: Check API connectivity and ensure the MNO service is working

#### 5. "Payment initiation failed"
**Solution**: Verify the phone number format and operator selection

### Debug Information

#### Check Logs
- Application logs: `storage/logs/pawapay.log`
- Webhook logs: `logs/webhook_test.log`
- PHP error logs: Check server error logs

#### API Response Debugging
- Enable debug mode in .env: `APP_DEBUG=true`
- Check network tab in browser developer tools
- Use tools like Postman to test API endpoints directly

## Test Results Documentation

### Recording Test Results
For each test case, record:
- Test date and time
- Phone number used
- Operator selected
- Amount tested
- Expected result
- Actual result
- Any error messages
- Screenshots if applicable

### Test Report Format
```markdown
## Test Case: [Test Name]
- **Date**: [YYYY-MM-DD HH:MM]
- **Phone**: [phone number]
- **Operator**: [operator code]
- **Amount**: [amount] ZMW
- **Expected**: [expected result]
- **Actual**: [actual result]
- **Status**: ✅ PASS / ❌ FAIL
- **Notes**: [additional notes]
```

## Next Steps

After completing all tests:
1. Document any issues found
2. Verify all critical functionality works
3. Prepare for production deployment
4. Update production configuration
5. Set up monitoring and alerting

## Support

For issues during testing:
1. Check the troubleshooting section above
2. Review the application logs
3. Test API connectivity directly
4. Verify webhook endpoint accessibility
5. Contact PawaPay support if API issues persist