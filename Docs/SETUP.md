# PawaPay Integration Setup Guide

## System Requirements

### PHP Requirements
- PHP 7.4 or higher
- Required Extensions:
  - `json`
  - `curl`
  - `mbstring`
  - `openssl`
  - `bcmath` (for money calculations)
  - `pdo` (for database integration)

### Server Requirements
- HTTPS enabled for production environment
- Proper SSL certificate configuration
- Minimum 512MB memory limit for PHP
- `post_max_size` and `upload_max_filesize` of at least 8M
- `max_execution_time` minimum 30 seconds

## Initial Setup

### 1. Environment Setup
```bash
# Copy environment template
cp .env.example .env

# Generate webhook secret
php -r "echo bin2hex(random_bytes(32));" > webhook_secret.txt
```

### 2. Composer Installation
```bash
# Using composer.phar locally
php composer.phar install

# Install development dependencies
php composer.phar install --dev
```

### 3. Database Setup
```bash
# Create database tables
php migrations/install.php

# Verify database connection
php test_db_connection.php
```

### 4. Configuration Verification
```bash
# Verify PHP configuration
php verify_requirements.php

# Test API connection
php test_api_connection.php
```

## Production Deployment Checklist

1. Environment Configuration:
   - Set `APP_ENV=production`
   - Configure secure API keys
   - Set up proper webhook URLs
   - Enable error logging
   - Disable debug mode

2. Security Measures:
   - Enable HTTPS only
   - Set secure cookie settings
   - Configure CORS properly
   - Set up rate limiting
   - Enable request signing

3. Performance Optimization:
   - Enable OPcache
   - Configure proper PHP-FPM settings
   - Set up caching for API responses
   - Configure session handling

4. Monitoring Setup:
   - Configure error logging
   - Set up performance monitoring
   - Enable transaction tracking
   - Configure alerts

## Testing

```bash
# Run all tests
php composer.phar test

# Run specific test suites
php composer.phar test -- --testsuite=unit
php composer.phar test -- --testsuite=integration
```

## Maintenance

### Regular Checks
1. Monitor error logs daily
2. Check API response times
3. Verify webhook deliveries
4. Monitor transaction success rates
5. Check system resource usage

### Updates
1. Keep dependencies updated:
   ```bash
   php composer.phar update --with-dependencies
   ```
2. Review API changelog regularly
3. Update SSL certificates before expiry
4. Review and update security measures

## Troubleshooting

### Common Issues
1. API Connection Issues:
   - Verify API credentials
   - Check network connectivity
   - Verify SSL certificate validity

2. Webhook Issues:
   - Verify webhook URL accessibility
   - Check signature verification
   - Monitor webhook logs

3. Transaction Issues:
   - Check transaction logs
   - Verify payment provider status
   - Check currency configurations

### Debug Mode
To enable debug mode for development:
```php
define('PAWAPAY_DEBUG', true);
```

## Support and Resources
- API Documentation: https://docs.pawapay.io
- Support Email: support@pawapay.io
- Integration Guide: [integration_guide.md](./docs/integration_guide.md)