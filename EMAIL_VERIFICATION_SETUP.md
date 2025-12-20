# 📧 Email Verification Setup Guide

## Overview

This system implements email verification for new user registrations. After registering, users must verify their email address before accessing the dashboard and system features.

---

## How It Works

### Registration Flow

1. **User Registers** → User fills registration form
2. **Account Created** → User account created but `email_verified_at` is NULL
3. **Email Sent** → Verification email sent automatically
4. **User Clicks Link** → User receives email with verification link
5. **Email Verified** → Link verifies email and sets `email_verified_at` timestamp
6. **Access Granted** → User can now access dashboard and features

### Verification Link

The verification link is a **signed URL** that:
- Contains user ID and email hash
- Expires in **60 minutes**
- Is unique and secure
- Can only be used once

Example URL:
```
http://localhost:8000/email/verify/1/abc123def?expires=1234567890&signature=xyz789
```

---

## Email Configuration

### 1. Using Gmail (Recommended for Testing)

#### Step 1: Enable 2-Step Verification
1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable **2-Step Verification**

#### Step 2: Generate App Password
1. Go to [App Passwords](https://myaccount.google.com/apppasswords)
2. Select **Mail** and **Other (Custom name)**
3. Enter name: "BookHub Library"
4. Click **Generate**
5. Copy the 16-character password (e.g., `abcd efgh ijkl mnop`)

#### Step 3: Update .env File
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=abcdefghijklmnop  # App password (no spaces)
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@bookhub.com
MAIL_FROM_NAME="BookHub Library"
```

---

### 2. Using Mailtrap (Development/Testing)

Mailtrap captures all emails in a fake inbox - perfect for testing!

#### Step 1: Create Account
1. Go to [Mailtrap.io](https://mailtrap.io)
2. Sign up for free account
3. Go to **Email Testing** → **Inboxes**

#### Step 2: Get SMTP Credentials
1. Select your inbox
2. Copy SMTP credentials from **SMTP Settings**

#### Step 3: Update .env File
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@bookhub.com
MAIL_FROM_NAME="BookHub Library"
```

---

### 3. Using Mailgun (Production)

For production environments with high email volume.

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.yourdomain.com
MAILGUN_SECRET=your-mailgun-api-key
MAIL_FROM_ADDRESS=noreply@bookhub.com
MAIL_FROM_NAME="BookHub Library"
```

---

## Testing Email Verification

### Method 1: Check Mail Log (Development)
If `MAIL_MAILER=log`, emails are saved to:
```
storage/logs/laravel.log
```

Search for the verification URL in the log file.

### Method 2: Use Mailtrap
1. Set up Mailtrap (see above)
2. Register a test user
3. Check Mailtrap inbox for email
4. Click verification link

### Method 3: Manual Testing (Development Only)
If emails aren't working, manually verify a user:

```php
// In php artisan tinker
$user = \App\Models\User::find(1);
$user->markEmailAsVerified();
```

Or run SQL:
```sql
UPDATE users SET email_verified_at = NOW() WHERE id = 1;
```

---

## Routes

### Email Verification Routes

| Route | Method | Description |
|-------|--------|-------------|
| `/email/verify` | GET | Show verification notice page |
| `/email/verify/{id}/{hash}` | GET | Verify email (from email link) |
| `/email/resend` | POST | Resend verification email |

### Protected Routes

Routes with `->middleware('verified')` require email verification:
- `/dashboard` - Dashboard access
- All user management routes
- All book management routes
- All borrowing/reservation routes

---

## User Experience

### 1. Registration
User registers → Sees verification notice page:
```
✉️ Verify Your Email
A verification email has been sent to your email address.
Please check your inbox and click on the verification link.
```

### 2. Email Received
User receives professional email with:
- Welcome message
- Verification button
- Expiration notice (60 minutes)
- Security message

### 3. Email Verified
User clicks link → Redirected to dashboard:
```
✅ Email verified successfully! Welcome to the library system.
```

### 4. Resend Option
If user doesn't receive email:
- Click "Resend Verification Email" button
- New email sent immediately
- Success message displayed

---

## Security Features

### 1. Signed URLs
- Verification links use Laravel's signed URL feature
- Links expire after 60 minutes
- Tampering invalidates the link

### 2. Hash Verification
- Email hash prevents unauthorized verification
- Hash: `sha1($user->email)`
- Must match exactly

### 3. Already Verified Check
- Prevents duplicate verifications
- Redirects to dashboard if already verified

### 4. Login Protection
Routes protected with `verified` middleware:
- Users cannot access features without verification
- Automatically redirected to verification notice

---

## Troubleshooting

### Issue: Emails Not Sending

**Check 1: MAIL_MAILER Setting**
```bash
php artisan config:clear
php artisan cache:clear
```

**Check 2: .env Configuration**
- Verify all MAIL_* variables are set
- No extra spaces in password
- Correct port number

**Check 3: Gmail App Password**
- Must use App Password, not regular password
- 2-Step Verification must be enabled
- Remove spaces from app password

**Check 4: Test Email Manually**
```bash
php artisan tinker
```
```php
Mail::raw('Test email', function($message) {
    $message->to('test@example.com')->subject('Test');
});
```

### Issue: Verification Link Expired

**Solution:**
User can click "Resend Verification Email" button on verification notice page.

### Issue: User Bypassing Verification

**Check Routes:**
Ensure protected routes have `->middleware('verified')`:
```php
Route::get('/dashboard', [AuthController::class, 'dashboard'])
    ->middleware('verified');
```

---

## Email Template Customization

Edit the notification:
```
app/Notifications/VerifyEmailNotification.php
```

Customize:
- Email subject
- Welcome message
- Button text
- Email footer
- Company branding

Example:
```php
return (new MailMessage)
    ->subject('Welcome to BookHub - Verify Your Email')
    ->greeting('Welcome ' . $notifiable->name . '!')
    ->line('Your custom message here...')
    ->action('Verify Email', $verificationUrl);
```

---

## Admin Notes

### Manually Verify User (Emergency)

If a user needs immediate access:

**Method 1: Database**
```sql
UPDATE users 
SET email_verified_at = NOW() 
WHERE email = 'user@example.com';
```

**Method 2: Tinker**
```bash
php artisan tinker
```
```php
$user = User::where('email', 'user@example.com')->first();
$user->markEmailAsVerified();
```

### Disable Email Verification (Not Recommended)

If you need to temporarily disable:

1. Remove `implements MustVerifyEmail` from User model
2. Remove `->middleware('verified')` from routes
3. Comment out email sending in registration

---

## Production Checklist

- [ ] Configure production SMTP (Mailgun/SendGrid/SES)
- [ ] Set correct `MAIL_FROM_ADDRESS` with real domain
- [ ] Test email delivery
- [ ] Monitor email queue
- [ ] Set up email logs/monitoring
- [ ] Configure SPF/DKIM records for domain
- [ ] Add email templates branding
- [ ] Test verification link expiration
- [ ] Set up email bounce handling

---

## File Structure

```
app/
├── Http/
│   └── Controllers/
│       └── AuthController.php          # Email verification methods
├── Models/
│   └── User.php                        # MustVerifyEmail interface
└── Notifications/
    └── VerifyEmailNotification.php     # Email template

resources/
└── views/
    └── auth/
        └── verify-email.blade.php      # Verification notice page

routes/
└── web.php                             # Email verification routes
```

---

## References

- [Laravel Email Verification Docs](https://laravel.com/docs/verification)
- [Gmail App Passwords](https://support.google.com/accounts/answer/185833)
- [Mailtrap Setup](https://mailtrap.io/blog/laravel-send-email-gmail/)
- [Laravel Mail Configuration](https://laravel.com/docs/mail)
