# ZegoCloud Audio & Video Calling Package for Laravel

[![Latest Version](https://img.shields.io/packagist/v/yourusername/zego-audio-video-calling.svg)](https://packagist.org/packages/yourusername/zego-audio-video-calling)
[![Total Downloads](https://img.shields.io/packagist/dt/yourusername/zego-audio-video-calling.svg)](https://packagist.org/packages/yourusername/zego-audio-video-calling)
[![License](https://img.shields.io/packagist/l/yourusername/zego-audio-video-calling.svg)](https://packagist.org/packages/yourusername/zego-audio-video-calling)

A complete, production-ready Laravel package for implementing ZegoCloud audio and video calling with mobile app support. Just install, configure credentials, and you're ready to go!

## Features

✅ **Zero Configuration Setup** - Just add your ZegoCloud credentials to `.env`
✅ **Audio & Video Calling** - Full support for both audio and video calls
✅ **Web & Mobile Support** - Works seamlessly across web browsers and mobile apps
✅ **Real-time Communication** - WebSocket broadcasting for call state synchronization
✅ **Push Notifications** - FCM (Android) and APNs (iOS) support for incoming calls
✅ **Call History** - Track and manage call records
✅ **Laravel 10-12+ Compatible** - Supports Laravel 10, 11, 12 and future versions
✅ **Auto-Discovery** - Service provider auto-registration
✅ **Customizable UI** - Beautiful, responsive call interface out of the box
✅ **Mobile API** - RESTful API endpoints for mobile app integration

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Web Integration](#web-integration)
  - [API Integration](#api-integration)
- [Mobile App Integration](#mobile-app-integration)
- [Customization](#customization)
- [Broadcasting Setup](#broadcasting-setup)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- ZegoCloud account ([Get one here](https://console.zegocloud.com))
- Laravel Sanctum (for API authentication)
- Laravel Echo & Pusher (for real-time broadcasting)

## Installation

### Step 1: Install via Composer

```bash
composer require yourusername/zego-audio-video-calling
```

### Step 2: Publish Package Assets

```bash
php artisan vendor:publish --provider="ZegoAudioVideoCalling\ZegoAudioVideoCallingServiceProvider"
```

Or publish individually:

```bash
# Publish configuration
php artisan vendor:publish --tag=zego-calling-config

# Publish migrations
php artisan vendor:publish --tag=zego-calling-migrations

# Publish views (optional, for customization)
php artisan vendor:publish --tag=zego-calling-views

# Publish assets (CSS/JS)
php artisan vendor:publish --tag=zego-calling-assets
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

This will create:
- `calls` table - Stores call records
- Add device-related columns to `users` table (device_token, device_platform, is_online, last_seen)

## Configuration

### Step 1: Add ZegoCloud Credentials to `.env`

```env
# ZegoCloud Configuration
ZEGOCLOUD_APP_ID=your_app_id_here
ZEGOCLOUD_SERVER_SECRET=your_server_secret_here

# Push Notifications (Optional - for mobile apps)
ZEGO_PUSH_NOTIFICATIONS_ENABLED=true

# FCM Configuration (Android)
FCM_SERVER_KEY=your_fcm_server_key

# APNs Configuration (iOS)
APN_KEY_ID=your_apn_key_id
APN_TEAM_ID=your_apple_team_id
APN_BUNDLE_ID=com.yourapp.bundleid
APN_KEY_PATH=/path/to/AuthKey.p8
APN_PRODUCTION=false

# Broadcasting Configuration
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=your_cluster

# Optional Settings
ZEGO_MAX_CALL_DURATION=3600
ZEGO_AUTO_END_MISSED_CALLS=true
ZEGO_MISSED_CALL_TIMEOUT=60
```

### Step 2: Get Your ZegoCloud Credentials

1. Go to [ZegoCloud Console](https://console.zegocloud.com)
2. Create a new project or select existing one
3. Get your App ID and Server Secret from the project settings

That's it! The package is now ready to use.

## Usage

### Web Integration

#### Basic Implementation

Add call buttons to your Blade template:

```blade
<!-- In your chat or user profile page -->
@auth
    <div class="call-buttons">
        <button onclick="initiateVideoCall({{ $user->id }})">
            📹 Video Call
        </button>
        <button onclick="initiateAudioCall({{ $user->id }})">
            📞 Audio Call
        </button>
    </div>

    <!-- Include the call initiator script -->
    <script src="{{ asset('vendor/zego-calling/js/call-initiator.js') }}"></script>
    <script>
        const csrfToken = '{{ csrf_token() }}';

        function initiateVideoCall(userId) {
            ZegoCloudCaller.initiateCall(userId, 'video', csrfToken);
        }

        function initiateAudioCall(userId) {
            ZegoCloudCaller.initiateCall(userId, 'audio', csrfToken);
        }
    </script>
@endauth
```

#### Using Helper Methods

Or use the built-in button creators:

```blade
<script src="{{ asset('vendor/zego-calling/js/call-initiator.js') }}"></script>
<div id="call-buttons"></div>

<script>
    const csrfToken = '{{ csrf_token() }}';
    const userId = {{ $user->id }};
    const callButtons = document.getElementById('call-buttons');

    // Add video call button
    callButtons.appendChild(
        ZegoCloudCaller.createVideoCallButton(userId, csrfToken)
    );

    // Add audio call button
    callButtons.appendChild(
        ZegoCloudCaller.createAudioCallButton(userId, csrfToken)
    );
</script>
```

#### Programmatic Call Initiation

You can also initiate calls programmatically via AJAX:

```javascript
// Initiate video call
fetch('/call/initiate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({
        receiver_id: userId,
        call_type: 'video'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        window.location.href = data.room_url;
    }
});
```

### API Integration

The package provides RESTful API endpoints for both web and mobile applications.

#### Web API Endpoints

Base URL: `/api/call` (requires authentication via Sanctum)

```http
POST   /api/call/initiate            # Initiate a new call
POST   /api/call/{call}/accept       # Accept an incoming call
POST   /api/call/{call}/reject       # Reject an incoming call
POST   /api/call/{call}/end          # End an active call
GET    /api/call/{call}/details      # Get call details
POST   /api/call/generate-token      # Generate ZegoCloud token
```

#### Mobile API Endpoints

Base URL: `/api/mobile/call` (requires authentication via Sanctum)

```http
POST   /api/mobile/call/register-device                # Register device for push notifications
POST   /api/mobile/call/update-online-status           # Update user online status
POST   /api/mobile/call/initiate                       # Initiate a call
POST   /api/mobile/call/{callId}/accept                # Accept a call
POST   /api/mobile/call/{callId}/reject                # Reject a call
POST   /api/mobile/call/{callId}/end                   # End a call
GET    /api/mobile/call/active-calls                   # Get active calls
GET    /api/mobile/call/call-history                   # Get call history
GET    /api/mobile/call/{callId}/details               # Get call details
GET    /api/mobile/call/user/{userId}/availability     # Check user availability
POST   /api/mobile/call/generate-token                 # Generate token
```

#### Example: Initiate Call (API)

```php
// Request
POST /api/call/initiate
Content-Type: application/json
Authorization: Bearer {token}

{
    "receiver_id": 2,
    "call_type": "video"
}

// Response
{
    "success": true,
    "call": {
        "id": 123,
        "caller_id": 1,
        "receiver_id": 2,
        "room_id": "room_xyz123_1638475647",
        "call_type": "video",
        "status": "initiated",
        "created_at": "2024-01-15T10:30:00Z"
    },
    "room_url": "/call/call-page?roomID=room_xyz123_1638475647&type=video&chatWith=2"
}
```

## Mobile App Integration

For mobile app integration (iOS/Android), see the comprehensive guide:

**[📱 Mobile Integration Guide](MOBILE_INTEGRATION.md)**

The mobile integration guide includes:
- Complete Android (Kotlin) implementation
- Complete iOS (Swift) implementation
- Push notification setup for both platforms
- WebSocket integration for real-time updates
- ZegoCloud SDK integration
- Code examples and best practices

### Quick Start for Mobile Developers

1. **Register Device**:
```http
POST /api/mobile/call/register-device
{
    "device_token": "fcm_or_apn_device_token",
    "device_platform": "android" // or "ios"
}
```

2. **Listen for Incoming Calls** via:
   - Push notifications (when app is in background)
   - WebSocket events (when app is active)

3. **Accept/Reject Calls**:
```http
POST /api/mobile/call/{callId}/accept
POST /api/mobile/call/{callId}/reject
```

4. **Join ZegoCloud Room** using the provided configuration in the response

## Customization

### Customizing Routes

Edit `config/zego-calling.php`:

```php
'routes' => [
    'web' => [
        'enabled' => true,
        'prefix' => 'call',                    // Change route prefix
        'middleware' => ['web', 'auth'],       // Add custom middleware
    ],
    'mobile' => [
        'enabled' => true,
        'prefix' => 'api/mobile/call',
        'middleware' => ['api', 'auth:sanctum'],
    ],
],
```

### Customizing the UI

Publish the views:

```bash
php artisan vendor:publish --tag=zego-calling-views
```

Then edit the views in `resources/views/vendor/zego-calling/`.

### Customizing the User Model

In `config/zego-calling.php`:

```php
'user_model' => App\Models\User::class,
```

### Call Settings

```php
'call_settings' => [
    'max_call_duration' => 3600,              // Maximum call duration in seconds
    'auto_end_missed_calls' => true,          // Auto-end missed calls
    'missed_call_timeout' => 60,              // Missed call timeout in seconds
    'enable_call_history' => true,            // Enable call history tracking
],
```

## Broadcasting Setup

The package uses Laravel Broadcasting for real-time call state synchronization. You need to configure broadcasting in your Laravel application.

### Step 1: Install Laravel Echo and Pusher

```bash
npm install --save-dev laravel-echo pusher-js
```

### Step 2: Configure Broadcasting

In `resources/js/bootstrap.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true
});
```

### Step 3: Listen for Call Events

```javascript
// Listen for incoming calls
Echo.private(`user.${userId}`)
    .listen('.call.initiated', (e) => {
        console.log('Incoming call from:', e.caller.name);
        // Show incoming call notification
        showIncomingCallNotification(e);
    })
    .listen('.call.accepted', (e) => {
        console.log('Call accepted');
        // Join call room
    })
    .listen('.call.rejected', (e) => {
        console.log('Call rejected');
        // Show rejection message
    })
    .listen('.call.ended', (e) => {
        console.log('Call ended');
        // Close call interface
    });
```

## Testing

Run the package tests:

```bash
composer test
```

Or run specific test suites:

```bash
# Test call initiation
php artisan test --filter CallInitiationTest

# Test mobile API
php artisan test --filter MobileApiTest
```

## Troubleshooting

### Calls Not Connecting

1. **Check ZegoCloud Credentials**: Ensure `ZEGOCLOUD_APP_ID` and `ZEGOCLOUD_SERVER_SECRET` are correct in `.env`
2. **Verify Broadcasting Setup**: Make sure Laravel Echo is properly configured
3. **Check Firewall**: Ensure WebSocket ports are open

### Push Notifications Not Working

1. **FCM (Android)**:
   - Verify `FCM_SERVER_KEY` in `.env`
   - Check device token registration
   - Ensure Firebase is properly configured in your mobile app

2. **APNs (iOS)**:
   - Verify all APN credentials are correct
   - Ensure certificates are not expired
   - Check bundle ID matches

### WebSocket Connection Fails

1. Check `.env` broadcasting configuration
2. Verify Pusher credentials
3. Ensure Laravel Echo is imported and initialized
4. Check browser console for connection errors

### Video/Audio Not Working

1. **Check Browser Permissions**: Ensure camera/microphone access is granted
2. **HTTPS Required**: ZegoCloud requires HTTPS in production
3. **Check Device Compatibility**: Verify browser supports WebRTC

## Upgrade Guide

### From 1.x to 2.x

```bash
# Backup your database
php artisan backup:run

# Update package
composer update yourusername/zego-audio-video-calling

# Publish new assets
php artisan vendor:publish --tag=zego-calling-config --force

# Run migrations
php artisan migrate

# Clear cache
php artisan config:clear
php artisan view:clear
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Security

If you discover any security-related issues, please email your.email@example.com instead of using the issue tracker.

## Credits

- [Your Name](https://github.com/yourusername)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

- 📧 Email: your.email@example.com
- 🐛 Issues: [GitHub Issues](https://github.com/yourusername/zego-audio-video-calling/issues)
- 📖 Documentation: [Full Documentation](https://github.com/yourusername/zego-audio-video-calling/wiki)
- 💬 Discussions: [GitHub Discussions](https://github.com/yourusername/zego-audio-video-calling/discussions)

---

Made with ❤️ by [Your Name](https://github.com/yourusername)
