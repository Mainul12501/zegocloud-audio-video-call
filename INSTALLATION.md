# Installation Guide
## ZegoCloud Audio & Video Calling Package

Complete step-by-step installation guide for Laravel developers.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Quick Installation](#quick-installation)
3. [Detailed Installation](#detailed-installation)
4. [Configuration](#configuration)
5. [Testing Installation](#testing-installation)
6. [Troubleshooting](#troubleshooting)

---

## System Requirements

Before installing, ensure your system meets these requirements:

### Required
- PHP 8.1 or higher
- Laravel 10.0 or higher
- MySQL 5.7+ / PostgreSQL 9.6+ / SQLite 3.8+
- Composer 2.0+
- Node.js 16+ and NPM 8+ (for asset compilation)

### Recommended
- Laravel 11 or 12
- PHP 8.2 or 8.3
- Redis (for broadcasting)

### External Services
- ZegoCloud Account ([Sign up here](https://console.zegocloud.com))
- Pusher Account (for broadcasting) OR self-hosted WebSocket server
- Firebase Project (for Android push notifications)
- Apple Developer Account (for iOS push notifications)

---

## Quick Installation

For those who want to get started quickly:

```bash
# Step 1: Install package
composer require yourusername/zego-audio-video-calling

# Step 2: Publish assets
php artisan vendor:publish --provider="ZegoAudioVideoCalling\ZegoAudioVideoCallingServiceProvider"

# Step 3: Run migrations
php artisan migrate

# Step 4: Add credentials to .env
# ZEGOCLOUD_APP_ID=your_app_id
# ZEGOCLOUD_SERVER_SECRET=your_server_secret

# Step 5: Done! You can now use the calling features
```

---

## Detailed Installation

### Step 1: Install via Composer

Open your terminal in your Laravel project root and run:

```bash
composer require yourusername/zego-audio-video-calling
```

This will download and install the package along with its dependencies.

### Step 2: Service Provider Registration

**For Laravel 11+**: The package uses auto-discovery. No action needed!

**For Laravel 10**: If auto-discovery doesn't work, manually register in `config/app.php`:

```php
'providers' => [
    // Other providers...
    ZegoAudioVideoCalling\ZegoAudioVideoCallingServiceProvider::class,
],
```

### Step 3: Publish Package Files

Publish all package files:

```bash
php artisan vendor:publish --provider="ZegoAudioVideoCalling\ZegoAudioVideoCallingServiceProvider"
```

Or publish selectively:

```bash
# Configuration file
php artisan vendor:publish --tag=zego-calling-config

# Database migrations
php artisan vendor:publish --tag=zego-calling-migrations

# Views (optional, only if you want to customize)
php artisan vendor:publish --tag=zego-calling-views

# JavaScript and CSS assets
php artisan vendor:publish --tag=zego-calling-assets
```

This creates:
- `config/zego-calling.php` - Configuration file
- `database/migrations/*` - Database migration files
- `resources/views/vendor/zego-calling/*` - View templates (if published)
- `public/vendor/zego-calling/*` - JavaScript and CSS files

### Step 4: Run Migrations

Run the migrations to create necessary database tables:

```bash
php artisan migrate
```

This creates:
- `calls` table
- Adds columns to `users` table:
  - `device_token` - For push notifications
  - `device_platform` - User's platform (ios/android/web)
  - `is_online` - Online status
  - `last_seen` - Last activity timestamp

If you encounter errors, see [Migration Troubleshooting](#migration-issues).

### Step 5: Install Laravel Sanctum (if not already installed)

The package uses Laravel Sanctum for API authentication:

```bash
php artisan install:api
```

This command:
- Installs Laravel Sanctum
- Publishes Sanctum configuration and migrations
- Adds necessary middleware

### Step 6: Install Laravel Broadcasting (for real-time features)

Install Laravel Echo and Pusher:

```bash
npm install --save-dev laravel-echo pusher-js
```

If you prefer using a different broadcasting driver, configure it in `config/broadcasting.php`.

---

## Configuration

### Step 1: Get ZegoCloud Credentials

1. Visit [ZegoCloud Console](https://console.zegocloud.com)
2. Sign up or log in
3. Create a new project or select an existing one
4. Navigate to **Project Settings**
5. Copy your **App ID** and **Server Secret**

### Step 2: Configure Environment Variables

Add these to your `.env` file:

```env
# ===================================
# ZegoCloud Configuration (REQUIRED)
# ===================================
ZEGOCLOUD_APP_ID=your_zegocloud_app_id
ZEGOCLOUD_SERVER_SECRET=your_zegocloud_server_secret

# ===================================
# Broadcasting Configuration (REQUIRED for real-time features)
# ===================================
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=mt1

# ===================================
# Push Notifications (OPTIONAL - for mobile apps)
# ===================================
ZEGO_PUSH_NOTIFICATIONS_ENABLED=true

# Firebase Cloud Messaging (Android)
FCM_SERVER_KEY=your_fcm_server_key

# Apple Push Notifications (iOS)
APN_KEY_ID=your_apn_key_id
APN_TEAM_ID=your_apple_team_id
APN_BUNDLE_ID=com.yourapp.bundle
APN_KEY_PATH=/path/to/AuthKey.p8
APN_PRODUCTION=false

# ===================================
# Optional Settings
# ===================================
ZEGO_MAX_CALL_DURATION=3600
ZEGO_AUTO_END_MISSED_CALLS=true
ZEGO_MISSED_CALL_TIMEOUT=60
ZEGO_ENABLE_CALL_HISTORY=true
```

### Step 3: Configure Broadcasting

Edit `resources/js/bootstrap.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    encrypted: true
});
```

Add to your `.env`:

```env
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### Step 4: Compile Assets

```bash
npm install
npm run build
```

### Step 5: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Testing Installation

### Test 1: Verify Routes

```bash
php artisan route:list | grep zego
```

You should see routes like:
```
GET|HEAD  call/call-page ................ zego-calling.call-page
POST      call/initiate ................. zego-calling.initiate
POST      api/mobile/call/initiate ...... zego-calling.mobile.initiate
```

### Test 2: Verify Configuration

Create a test route in `routes/web.php`:

```php
Route::get('/test-zego', function () {
    $config = config('zego-calling');
    return response()->json([
        'app_id' => $config['zegocloud']['app_id'],
        'configured' => !empty($config['zegocloud']['app_id'])
    ]);
})->middleware('web');
```

Visit `http://yourapp.test/test-zego` - you should see your app ID.

### Test 3: Test Database

```bash
php artisan tinker
```

```php
// Check if calls table exists
DB::table('calls')->count();

// Check if user table has new columns
Schema::hasColumn('users', 'device_token');
// Should return true
```

### Test 4: Test Call Functionality

1. Log in to your application
2. Navigate to a user profile or chat page
3. Add a call button:

```blade
<button onclick="testCall()">Test Video Call</button>

<script src="{{ asset('vendor/zego-calling/js/call-initiator.js') }}"></script>
<script>
function testCall() {
    ZegoCloudCaller.showNotification('Test successful!', 'success');
}
</script>
```

If you see a success notification, the package is installed correctly!

---

## Troubleshooting

### Migration Issues

#### Problem: Migration fails with "Table already exists"

**Solution**:
```bash
# Check existing migrations
php artisan migrate:status

# If the table exists but migration shows as pending:
php artisan migrate --pretend

# Manually mark as migrated if needed
# (Replace with actual migration name)
php artisan migrate --path=database/migrations/2024_01_01_000001_create_calls_table.php
```

#### Problem: Column already exists error

**Solution**: The package checks for existing columns. If you still get this error:
```bash
# Rollback the specific migration
php artisan migrate:rollback --step=1

# Or manually remove problematic columns
php artisan tinker
Schema::dropColumns('users', ['device_token', 'device_platform']);
exit

# Then run migration again
php artisan migrate
```

### Broadcasting Issues

#### Problem: WebSocket not connecting

**Solution**:
1. Verify Pusher credentials in `.env`
2. Check browser console for connection errors
3. Ensure `BROADCAST_DRIVER=pusher` in `.env`
4. Run `php artisan config:clear`

#### Problem: Events not broadcasting

**Solution**:
```bash
# Check queue worker is running
php artisan queue:work

# Check broadcasting configuration
php artisan config:show broadcasting
```

### Asset Issues

#### Problem: JavaScript/CSS not loading

**Solution**:
```bash
# Republish assets
php artisan vendor:publish --tag=zego-calling-assets --force

# Clear view cache
php artisan view:clear

# If using Vite
npm run build
```

#### Problem: 404 on asset files

**Solution**:
1. Verify files exist in `public/vendor/zego-calling/`
2. Check web server configuration for serving static files
3. Clear browser cache

### Permission Issues

#### Problem: Permission denied when publishing assets

**Solution**:
```bash
# On Linux/Mac
sudo chown -R $USER:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# On Windows (run as administrator)
icacls "storage" /grant Users:F /t
icacls "bootstrap/cache" /grant Users:F /t
```

### ZegoCloud Connection Issues

#### Problem: Unable to connect to ZegoCloud

**Solution**:
1. Verify App ID and Server Secret in `.env`
2. Check if credentials are correct in ZegoCloud console
3. Ensure your domain is added to allowed domains in ZegoCloud console
4. Check firewall/network settings

---

## Post-Installation Steps

### 1. Set Up Broadcasting

Follow [Broadcasting Setup Guide](README.md#broadcasting-setup)

### 2. Configure Channels

Create `routes/channels.php` if it doesn't exist:

```php
<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

### 3. Update User Model (Optional)

Add relationships to your User model if needed:

```php
// app/Models/User.php
public function initiatedCalls()
{
    return $this->hasMany(\ZegoAudioVideoCalling\Models\Call::class, 'caller_id');
}

public function receivedCalls()
{
    return $this->hasMany(\ZegoAudioVideoCalling\Models\Call::class, 'receiver_id');
}
```

### 4. Set Up Mobile Apps

If you're building mobile apps, follow the [Mobile Integration Guide](MOBILE_INTEGRATION.md).

---

## Verifying Successful Installation

✅ Package installed via Composer
✅ Configuration file published
✅ Migrations ran successfully
✅ ZegoCloud credentials configured
✅ Broadcasting configured (if using real-time features)
✅ Assets compiled and published
✅ Routes registered
✅ Test call works

---

## Next Steps

1. Read the [Complete Documentation](README.md)
2. Implement calling features in your application
3. Customize the UI if needed
4. Set up mobile app integration (if applicable)
5. Configure push notifications (for mobile apps)

---

## Getting Help

If you encounter issues:

1. Check the [Troubleshooting section](#troubleshooting)
2. Review the [FAQ](README.md#troubleshooting)
3. Open an issue on [GitHub](https://github.com/yourusername/zego-audio-video-calling/issues)
4. Email support: your.email@example.com

---

**Congratulations! Your ZegoCloud calling package is now installed and ready to use!** 🎉
