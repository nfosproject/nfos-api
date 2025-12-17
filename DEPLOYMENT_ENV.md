# Deployment Environment Variables

## Database Configuration

Use these exact environment variables in your deployment platform:

```env
DB_CONNECTION=mysql
DB_HOST=db-a0654f1d-e0d8-4438-91f6-557f2bfb1292.ap-southeast-1.public.db.laravel.cloud
DB_PORT=3306
DB_DATABASE=main
DB_USERNAME=zbbuh6woeh38od7f
DB_PASSWORD=LEqO9pcEEQMEGdNIjTSZ
```

## Important Notes

1. **IP Whitelisting Required**: Laravel Cloud databases require IP whitelisting. You must whitelist your deployment server IPs in the Laravel Cloud dashboard.

2. **Common Deployment IPs to Whitelist**:
   - `10.213.0.0/16` (entire range - recommended)
   - Or specific IPs like `10.213.12.217`, `10.213.16.69`, etc.

3. **SSL Configuration**: The database configuration is already set up for SSL connections required by Laravel Cloud.

## Setting Environment Variables

### For Laravel Forge / Vapor:
Add these in your environment settings in the dashboard.

### For Vercel / Netlify / Other Platforms:
Add these in your project's environment variables section.

### For Manual Deployment:
Create a `.env` file in the `backend-laravel` directory with these values.

## Testing Connection

After setting environment variables, test the connection:

```bash
cd backend-laravel
php artisan db:test
```

## Troubleshooting

If you get "Access denied" errors:
1. Verify IP whitelisting in Laravel Cloud dashboard
2. Double-check all credentials are set correctly
3. Ensure no extra spaces or quotes in environment variables
4. Check that `DB_DATABASE=main` is set (not just the other variables)

See `DEPLOYMENT_TROUBLESHOOTING.md` for more details.


