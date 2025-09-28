# PawaPay v2 Integration Deployment Guide

This document outlines the steps and requirements for deploying the PawaPay v2 integration to production.

## Prerequisites

### System Requirements

- PHP >= 7.4
- MySQL >= 5.7
- Required PHP Extensions:
  - json
  - curl
  - mbstring
  - openssl
  - bcmath
  - pdo_mysql

### Environment Configuration

1. Copy the `.env.example` file to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Configure the following environment variables:
   ```
   APP_ENV=production
   APP_DEBUG=false
   
   # PawaPay Configuration
   PAWAPAY_API_TOKEN=your_api_token
   PAWAPAY_ENVIRONMENT=production
   PAWAPAY_WEBHOOK_SECRET=your_webhook_secret
   
   # Database Configuration
   DB_HOST=your_database_host
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   ```

## Deployment Steps

1. **Install Dependencies**
   ```bash
   php composer.phar install --no-dev --optimize-autoloader
   ```

2. **Update File Permissions**
   ```bash
   chmod 644 .env
   chmod -R 755 config/
   chmod -R 755 logs/
   ```

3. **Run the Deployment Script**
   ```bash
   php deploy.php
   ```
   This script will:
   - Verify environment configuration
   - Check database connection
   - Test API connectivity
   - Validate security settings
   - Optimize the autoloader
   - Clear caches
   - Run database migrations
   - Verify webhook configuration

4. **Configure Web Server**
   - Ensure HTTPS is enabled
   - Set up proper SSL/TLS certificates
   - Configure URL rewriting if needed

## Security Considerations

1. **File Permissions**
   - `.env` file should be readable only by the web server user
   - Configuration files should have restricted permissions
   - Log files should be outside web root

2. **SSL/TLS Configuration**
   - Enable HTTPS
   - Use strong SSL/TLS protocols (TLS 1.2+)
   - Configure proper SSL certificate

3. **Error Handling**
   - Ensure `APP_DEBUG` is set to `false`
   - Configure proper error logging
   - Set up error monitoring/alerting

4. **Database Security**
   - Use strong passwords
   - Restrict database user permissions
   - Enable SSL for database connections if possible

## Monitoring & Maintenance

1. **Log Monitoring**
   - Set up log rotation
   - Monitor error logs
   - Configure alerts for critical errors

2. **Performance Monitoring**
   - Monitor transaction processing times
   - Watch for failed transactions
   - Track API response times

3. **Regular Maintenance**
   - Keep PHP and dependencies updated
   - Monitor disk space
   - Review and rotate logs
   - Check for security updates

## Troubleshooting

1. **Common Issues**
   - If database migrations fail, check connection settings
   - For API connection issues, verify API token and environment
   - If webhooks fail, check webhook secret and SSL configuration

2. **Error Logs**
   - Check PHP error logs
   - Review application logs
   - Monitor webhook event logs

3. **Support**
   - Contact PawaPay support for API-related issues
   - Review PawaPay documentation
   - Check GitHub issues for known problems

## Testing After Deployment

1. **Sanity Checks**
   - Verify webhook endpoints are accessible
   - Test a small transaction
   - Check error logging

2. **Integration Tests**
   ```bash
   php vendor/bin/phpunit tests/Integration/
   ```

## Rollback Procedure

1. **If Deployment Fails**
   - Stop the deployment process
   - Check error logs
   - Restore database from backup if needed
   - Contact support if necessary

## Production Checklist

- [ ] Environment variables configured
- [ ] SSL certificates installed
- [ ] Database backups configured
- [ ] Error logging set up
- [ ] Monitoring in place
- [ ] Security hardening completed
- [ ] Integration tests passed
- [ ] Webhooks verified
- [ ] Performance tested
- [ ] Documentation updated

## Contact & Support

- PawaPay Support: [support@pawapay.com](mailto:support@pawapay.com)
- Technical Documentation: [https://docs.pawapay.io](https://docs.pawapay.io)
- Emergency Contact: [emergency@pawapay.com](mailto:emergency@pawapay.com)