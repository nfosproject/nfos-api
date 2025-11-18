# Email Configuration Guide

## Free SMTP Options for Local Development

### Option 1: Mailtrap (Recommended for Testing)
**Best for:** Catching and testing emails locally without sending real emails

1. **Sign up for free at:** https://mailtrap.io
2. **Get SMTP credentials:**
   - Go to https://mailtrap.io/inboxes
   - Create or select an inbox
   - Click on "SMTP Settings"
   - Choose "Laravel 9+" integration
   - Copy the credentials

3. **Update your `.env` file:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="test@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

4. **View emails:** All emails will appear in your Mailtrap inbox (not sent to real recipients)

**Free Tier:** 3,500 emails/month

---

### Option 2: Gmail SMTP (Free)
**Best for:** Actually sending real emails (requires Gmail account)

1. **Enable 2-Step Verification** on your Google Account
2. **Generate App Password:**
   - Go to https://myaccount.google.com/apppasswords
   - Select "Mail" and your device
   - Copy the 16-character password

3. **Update your `.env` file:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD=your_16_char_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your_gmail@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Note:** Gmail limits to 500 emails/day for free accounts

---

### Option 3: Mailgun (Free Tier)
**Best for:** Production-ready email delivery

1. **Sign up at:** https://www.mailgun.com
2. **Verify your domain** (or use sandbox domain for testing)
3. **Update your `.env` file:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@your-domain.mailgun.org
MAIL_PASSWORD=your_mailgun_password
MAIL_ENCRYPTION=tls
```

**Free Tier:** 5,000 emails/month for 3 months, then 1,000/month

---

### Option 4: Local Mail Server (Mailhog/Mailpit)
**Best for:** Completely local testing, no external services

#### Using Mailpit (Lightweight, recommended):
```bash
# Install Mailpit
brew install mailpit  # macOS
# or download from https://github.com/axllent/mailpit

# Run Mailpit
mailpit

# Access web UI at http://localhost:8025
```

Update `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

#### Using Mailhog:
```bash
# Install Mailhog
brew install mailhog  # macOS

# Run Mailhog
mailhog

# Access web UI at http://localhost:8025
```

---

### Option 5: Laravel Log Driver (Simplest)
**Best for:** Just viewing email content in log files

Update `.env`:
```env
MAIL_MAILER=log
```

Emails will be logged to `storage/logs/laravel.log`

---

## Current Configuration

After setting up, clear the config cache:
```bash
php artisan config:clear
```

## Testing Email Sending

Test if emails are working:
```bash
php artisan tinker
```

Then in tinker:
```php
Mail::raw('Test email', function($message) {
    $message->to('test@example.com')
            ->subject('Test Email');
});
```

For Mailtrap, check your inbox at https://mailtrap.io/inboxes

