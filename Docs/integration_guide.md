# PawaPay Integration Guide for Myzuwa.com

This guide explains how to integrate the PawaPay SDK into Myzuwa.com's Modesy installation.

## Quick Start

1. **Install the SDK**
   ```bash
   composer require myzuwa/pawapay-sdk
   ```

2. **Add Configuration**
   Add to your `.env` file:
   ```env
   PAWAPAY_PUBLIC_KEY=your_public_key
   PAWAPAY_SECRET_KEY=your_secret_key
   PAWAPAY_WEBHOOK_SECRET=your_webhook_secret
   PAWAPAY_ENVIRONMENT=sandbox
   ```

3. **Add Payment Gateway**
   Add PawaPay to the `payment_gateways` table:
   ```sql
   INSERT INTO payment_gateways 
   (name, name_key, public_key, secret_key, webhook_secret, environment, status, logos)
   VALUES 
   ('PawaPay', 'pawapay', 'YOUR_PUBLIC_KEY', 'YOUR_SECRET_KEY', 'YOUR_WEBHOOK_SECRET', 'sandbox', 1, 'pawapay-logo.svg');
   ```

## Integration Points

### 1. Payment View
Create `app/Views/cart/payment_methods/_pawapay.php`:
```php
<?php
// See example implementation in docs/examples/payment_view.md
?>
```

### 2. Controller Methods
Add to `app/Controllers/CartController.php`:
```php
// See example implementation in docs/examples/controller_methods.md
```

## Updating Process

When Modesy releases updates:

1. Back up your PawaPay integration files
2. Update Modesy core
3. Re-apply PawaPay integration using this guide
4. Test all payment flows

## Links to Other Documentation
- [Version Control](version_control.md)
- [API Documentation](pawapay_documentation.md)
- [Analysis Document](253Plan%20and%20Analysis.md)

## Support
For issues and questions, please refer to the [troubleshooting guide](troubleshooting.md).