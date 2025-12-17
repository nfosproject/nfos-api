# Deployment Troubleshooting Guide

## Database Connection Issues

### Error: "Access denied for user 'xxx'@'IP_ADDRESS'"

This error occurs when your deployment server cannot connect to your Laravel Cloud database due to IP whitelisting restrictions.

#### Solution: Whitelist Deployment Server IPs

Laravel Cloud databases use ProxySQL and require IP addresses to be whitelisted before connections are allowed.

**Steps to fix:**

1. **Identify your deployment server IPs**
   - Check your deployment logs for the IP address in the error message
   - Common IP ranges for deployment services:
     - `10.213.0.0/16` (common for many cloud providers)
     - Specific IPs like `10.213.12.217`, `10.213.16.69`, etc.

2. **Whitelist IPs in Laravel Cloud**
   - Log into your Laravel Cloud dashboard
   - Navigate to your database settings
   - Find the "IP Whitelist" or "Allowed IPs" section
   - Add your deployment server IP addresses or IP ranges
   - Save the changes

3. **For dynamic IPs (recommended)**
   - If your deployment service uses dynamic IPs, whitelist the entire IP range
   - Example: `10.213.0.0/16` covers all IPs from `10.213.0.0` to `10.213.255.255`
   - Contact your deployment provider to confirm their IP ranges

4. **Verify connection**
   ```bash
   php artisan db:test
   ```

### Environment Variables

Ensure these are set correctly in your deployment environment:

```env
DB_CONNECTION=mysql
DB_HOST=db-a0654f1d-e0d8-4438-91f6-557f2bfb1292.ap-southeast-1.public.db.laravel.cloud
DB_PORT=3306
DB_DATABASE=main
DB_USERNAME=zbbuh6woeh38od7f
DB_PASSWORD=LEqO9pcEEQMEGdNIjTSZ
```

**Note**: Make sure `DB_DATABASE=main` is included. The database name is required.

### Testing Database Connection Locally

Before deploying, test your database connection:

```bash
cd backend-laravel
php artisan db:test
```

### Common Issues

1. **Wrong credentials**: Double-check username and password
2. **IP not whitelisted**: Most common issue - ensure deployment IPs are whitelisted
3. **SSL/TLS requirements**: Laravel Cloud databases may require SSL (already configured)
4. **Network connectivity**: Ensure your deployment server can reach the database host

### Deployment Commands

If migrations fail during deployment, you can:

1. **Skip migrations temporarily** (not recommended for production):
   - Remove `php artisan migrate --force` from deployment scripts
   - Run migrations manually after fixing IP whitelisting

2. **Use graceful migrations**:
   ```bash
   php artisan migrate --graceful
   ```

3. **Test connection first**:
   ```bash
   php artisan db:test && php artisan migrate --force
   ```

### Getting Help

If issues persist:
1. Verify all environment variables are set correctly
2. Check Laravel Cloud database logs
3. Contact Laravel Cloud support with your database ID and deployment IPs
4. Ensure your database user has proper permissions

