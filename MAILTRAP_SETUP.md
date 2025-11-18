# Mailtrap SMTP Setup Guide

## How to Get Your Mailtrap Credentials

1. **Sign up/Login** at https://mailtrap.io

2. **Go to Inboxes:**
   - Click on "Inboxes" in the left sidebar
   - Create a new inbox or select an existing one

3. **Get SMTP Credentials:**
   - Click on your inbox
   - Look for "SMTP Settings" tab
   - Select **"Laravel 9+"** integration (or "SMTP" tab)
   - You'll see something like:
     ```
     Host: smtp.mailtrap.io
     Port: 2525
     Username: abc123def456... (one hash)
     Password: xyz789uvw012... (different hash)
     ```

4. **Copy BOTH values** - they are different!

5. **Update your `.env` file:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_actual_username_hash
MAIL_PASSWORD=your_actual_password_hash
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="test@example.com"
MAIL_FROM_NAME="NFOS Project"
```

6. **Clear config cache:**
```bash
php artisan config:clear
```

7. **Test sending:**
```bash
php artisan tinker
```

Then:
```php
Mail::raw('Test email', function($message) {
    $message->to('any@email.com')
            ->subject('Test');
});
```

8. **View emails:** Go back to https://mailtrap.io/inboxes and check your inbox!

---

## Quick Image Guide

```
Mailtrap Dashboard
  └─ Inboxes
      └─ [Your Inbox]
          └─ SMTP Settings (tab)
              └─ Select: "Laravel 9+" or "SMTP"
                  └─ Copy Username and Password (they're different!)
```

---

**Note:** The key you provided (`543fbb41c67db7a91940965df73c68f9`) might be:
- Just the username (need password too)
- Just the password (need username too)  
- An API key (not for SMTP, for API sending)

For SMTP, you need BOTH username and password from the SMTP Settings page.

