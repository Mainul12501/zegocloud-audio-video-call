# Mobile App Integration Guide
## ZegoCloud Audio & Video Calling Package

Complete guide for integrating the ZegoCloud calling system with your iOS and Android mobile applications.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Authentication](#authentication)
4. [API Endpoints](#api-endpoints)
5. [Android Integration](#android-integration)
6. [iOS Integration](#ios-integration)
7. [Push Notifications](#push-notifications)
8. [WebSocket Integration](#websocket-integration)
9. [Call Flow](#call-flow)
10. [Troubleshooting](#troubleshooting)

---

## Overview

This package provides a complete RESTful API for mobile apps to integrate audio and video calling features. The system supports:

- ✅ Cross-platform calling (Web ↔ Mobile, Mobile ↔ Mobile)
- ✅ Real-time call state synchronization via WebSocket
- ✅ Push notifications for incoming calls
- ✅ Call history and management
- ✅ Online/offline status tracking

### Architecture

```
Mobile App ─────► Backend API ─────► ZegoCloud
     │                 │
     │                 ├─► WebSocket (Real-time)
     │                 └─► Push Notifications
     │
     └─────► ZegoCloud SDK (for A/V streaming)
```

---

## Prerequisites

### Required SDKs

#### Android
```gradle
// Add to build.gradle (app level)
dependencies {
    // ZegoCloud SDK
    implementation 'com.github.zegolibrary:express-video:+'

    // Networking
    implementation 'com.squareup.retrofit2:retrofit:2.9.0'
    implementation 'com.squareup.retrofit2:converter-gson:2.9.0'

    // WebSocket
    implementation 'io.socket:socket.io-client:2.1.0'

    // Firebase (for push notifications)
    implementation 'com.google.firebase:firebase-messaging:23.0.0'
}
```

#### iOS
```ruby
# Add to Podfile
pod 'ZegoExpressEngine'
pod 'Alamofire'
pod 'Starscream'  # For WebSocket
```

### Required Credentials

1. **Backend API URL**: Your Laravel application URL
2. **ZegoCloud Credentials**: App ID and Server Secret (provided by backend)
3. **Push Notification Credentials**:
   - Android: Firebase project setup
   - iOS: APNs configuration

---

## Authentication

All API endpoints require authentication using Laravel Sanctum tokens.

### Step 1: User Login

**Endpoint**: `POST /api/auth/custom-login`

**Request**:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response**:
```json
{
  "success": true,
  "token": "1|abcdefghijklmnopqrstuvwxyz123456",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

### Step 2: Store Token

Store the token securely in your app and include it in all subsequent requests:

```
Authorization: Bearer {token}
```

---

## API Endpoints

### Base URL
```
https://yourdomain.com/api/mobile/call
```

### Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/register-device` | Register device for push notifications |
| POST | `/update-online-status` | Update user online/offline status |
| POST | `/initiate` | Start a new call |
| POST | `/{callId}/accept` | Accept an incoming call |
| POST | `/{callId}/reject` | Reject an incoming call |
| POST | `/{callId}/end` | End an active call |
| GET | `/active-calls` | Get list of active calls |
| GET | `/call-history` | Get call history (paginated) |
| GET | `/{callId}/details` | Get specific call details |
| GET | `/user/{userId}/availability` | Check if user is available |
| POST | `/generate-token` | Generate ZegoCloud configuration |

### Detailed API Documentation

#### 1. Register Device

**Endpoint**: `POST /api/mobile/call/register-device`

**Request**:
```json
{
  "device_token": "fcm_or_apn_device_token_here",
  "device_platform": "android"  // or "ios"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Device registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "device_platform": "android",
    "is_online": true
  }
}
```

#### 2. Initiate Call

**Endpoint**: `POST /api/mobile/call/initiate`

**Request**:
```json
{
  "receiver_id": 2,
  "call_type": "video"  // or "audio"
}
```

**Response**:
```json
{
  "success": true,
  "call": {
    "id": 123,
    "caller_id": 1,
    "receiver_id": 2,
    "room_id": "room_xyz123_1638475647",
    "call_type": "video",
    "status": "initiated"
  },
  "room_id": "room_xyz123_1638475647",
  "zegocloud_config": {
    "app_id": "1234567890",
    "server_secret": "your_server_secret",
    "room_id": "room_xyz123_1638475647",
    "user_id": "1",
    "user_name": "John Doe"
  }
}
```

#### 3. Accept Call

**Endpoint**: `POST /api/mobile/call/{callId}/accept`

**Response**:
```json
{
  "success": true,
  "call": {
    "id": 123,
    "status": "accepted",
    "started_at": "2024-01-15T10:30:00Z"
  },
  "zegocloud_config": {
    "app_id": "1234567890",
    "room_id": "room_xyz123_1638475647",
    "user_id": "2",
    "user_name": "Jane Doe"
  }
}
```

#### 4. Check User Availability

**Endpoint**: `GET /api/mobile/call/user/{userId}/availability`

**Response**:
```json
{
  "success": true,
  "user": {
    "id": 2,
    "name": "Jane Doe",
    "is_online": true,
    "last_seen": "2024-01-15T10:25:00Z"
  },
  "is_available": true,
  "has_active_call": false
}
```

---

## Android Integration

### Complete Implementation

#### 1. API Service Interface

```kotlin
// ApiService.kt
interface CallApiService {

    @POST("mobile/call/register-device")
    suspend fun registerDevice(
        @Body request: RegisterDeviceRequest
    ): Response<ApiResponse>

    @POST("mobile/call/initiate")
    suspend fun initiateCall(
        @Body request: InitiateCallRequest
    ): Response<CallResponse>

    @POST("mobile/call/{callId}/accept")
    suspend fun acceptCall(
        @Path("callId") callId: String
    ): Response<CallResponse>

    @POST("mobile/call/{callId}/reject")
    suspend fun rejectCall(
        @Path("callId") callId: String
    ): Response<ApiResponse>

    @POST("mobile/call/{callId}/end")
    suspend fun endCall(
        @Path("callId") callId: String
    ): Response<EndCallResponse>
}

// Data Classes
data class RegisterDeviceRequest(
    val device_token: String,
    val device_platform: String
)

data class InitiateCallRequest(
    val receiver_id: Int,
    val call_type: String
)

data class CallResponse(
    val success: Boolean,
    val call: Call,
    val zegocloud_config: ZegoConfig?
)

data class ZegoConfig(
    val app_id: String,
    val server_secret: String,
    val room_id: String,
    val user_id: String,
    val user_name: String
)
```

#### 2. Retrofit Setup

```kotlin
// RetrofitClient.kt
object RetrofitClient {
    private const val BASE_URL = "https://yourdomain.com/api/"

    private val okHttpClient = OkHttpClient.Builder()
        .addInterceptor { chain ->
            val original = chain.request()
            val requestBuilder = original.newBuilder()
                .header("Authorization", "Bearer ${getAuthToken()}")
                .header("Accept", "application/json")
            val request = requestBuilder.build()
            chain.proceed(request)
        }
        .build()

    val instance: CallApiService by lazy {
        Retrofit.Builder()
            .baseUrl(BASE_URL)
            .addConverterFactory(GsonConverterFactory.create())
            .client(okHttpClient)
            .build()
            .create(CallApiService::class.java)
    }
}
```

#### 3. ZegoCloud Manager

```kotlin
// ZegoManager.kt
class ZegoManager(private val context: Context) {

    private var engine: ZegoExpressEngine? = null
    private var localStream: ZegoStream? = null

    fun initEngine(appId: Long, appSign: String) {
        val profile = ZegoEngineProfile()
        profile.appID = appId
        profile.appSign = appSign
        profile.scenario = ZegoScenario.GENERAL
        profile.application = context.applicationContext as Application

        engine = ZegoExpressEngine.createEngine(profile, null)
    }

    fun loginRoom(roomId: String, userId: String, userName: String) {
        val user = ZegoUser(userId)
        user.userName = userName

        engine?.loginRoom(roomId, user)
    }

    fun startPublishing(streamId: String, view: TextureView) {
        engine?.startPreview(ZegoCanvas(view))
        engine?.startPublishingStream(streamId)
    }

    fun startPlaying(streamId: String, view: TextureView) {
        engine?.startPlayingStream(streamId, ZegoCanvas(view))
    }

    fun muteAudio(mute: Boolean) {
        engine?.muteMicrophone(mute)
    }

    fun muteVideo(mute: Boolean) {
        engine?.mutePublishStreamVideo(mute)
    }

    fun endCall(roomId: String) {
        engine?.logoutRoom(roomId)
    }

    fun destroy() {
        ZegoExpressEngine.destroyEngine(null)
    }
}
```

#### 4. Video Call Activity

```kotlin
// VideoCallActivity.kt
class VideoCallActivity : AppCompatActivity() {

    private lateinit var zegoManager: ZegoManager
    private lateinit var localView: TextureView
    private lateinit var remoteView: TextureView
    private var callId: String? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_video_call)

        localView = findViewById(R.id.local_view)
        remoteView = findViewById(R.id.remote_view)

        val zegoConfig = intent.getParcelableExtra<ZegoConfig>("zego_config")
        callId = intent.getStringExtra("call_id")

        if (zegoConfig != null) {
            setupCall(zegoConfig)
        }
    }

    private fun setupCall(config: ZegoConfig) {
        zegoManager = ZegoManager(this)
        zegoManager.initEngine(config.app_id.toLong(), config.server_secret)
        zegoManager.loginRoom(config.room_id, config.user_id, config.user_name)
        zegoManager.startPublishing("stream_${config.user_id}", localView)
    }

    fun endCall() {
        lifecycleScope.launch {
            callId?.let {
                RetrofitClient.instance.endCall(it)
            }
            zegoManager.destroy()
            finish()
        }
    }
}
```

---

## iOS Integration

### Complete Implementation

#### 1. API Service

```swift
// APIService.swift
class CallAPIService {

    static let shared = CallAPIService()
    let baseURL = "https://yourdomain.com/api/mobile/call"

    var headers: HTTPHeaders {
        return [
            "Authorization": "Bearer \(getAuthToken())",
            "Content-Type": "application/json"
        ]
    }

    func registerDevice(deviceToken: String, platform: String,
                       completion: @escaping (Result<ApiResponse, Error>) -> Void) {
        let parameters: [String: Any] = [
            "device_token": deviceToken,
            "device_platform": platform
        ]

        AF.request("\(baseURL)/register-device",
                   method: .post,
                   parameters: parameters,
                   encoding: JSONEncoding.default,
                   headers: headers)
            .responseDecodable(of: ApiResponse.self) { response in
                switch response.result {
                case .success(let data):
                    completion(.success(data))
                case .failure(let error):
                    completion(.failure(error))
                }
            }
    }

    func initiateCall(receiverId: Int, callType: String,
                     completion: @escaping (Result<CallResponse, Error>) -> Void) {
        let parameters: [String: Any] = [
            "receiver_id": receiverId,
            "call_type": callType
        ]

        AF.request("\(baseURL)/initiate",
                   method: .post,
                   parameters: parameters,
                   encoding: JSONEncoding.default,
                   headers: headers)
            .responseDecodable(of: CallResponse.self) { response in
                switch response.result {
                case .success(let data):
                    completion(.success(data))
                case .failure(let error):
                    completion(.failure(error))
                }
            }
    }

    func acceptCall(callId: String,
                   completion: @escaping (Result<CallResponse, Error>) -> Void) {
        AF.request("\(baseURL)/\(callId)/accept",
                   method: .post,
                   headers: headers)
            .responseDecodable(of: CallResponse.self) { response in
                switch response.result {
                case .success(let data):
                    completion(.success(data))
                case .failure(let error):
                    completion(.failure(error))
                }
            }
    }
}

// Models
struct CallResponse: Codable {
    let success: Bool
    let call: CallModel
    let zegocloud_config: ZegoConfig?
}

struct ZegoConfig: Codable {
    let app_id: String
    let server_secret: String
    let room_id: String
    let user_id: String
    let user_name: String
}
```

#### 2. ZegoCloud Manager

```swift
// ZegoManager.swift
import ZegoExpressEngine

class ZegoManager: NSObject {

    var engine: ZegoExpressEngine?

    func initEngine(appId: UInt32, appSign: String) {
        let profile = ZegoEngineProfile()
        profile.appID = appId
        profile.appSign = appSign
        profile.scenario = .general

        engine = ZegoExpressEngine.createEngine(with: profile, eventHandler: self)
    }

    func loginRoom(roomId: String, userId: String, userName: String) {
        let user = ZegoUser(userID: userId)
        user.userName = userName

        engine?.loginRoom(roomId, user: user)
    }

    func startPublishing(streamId: String, view: UIView) {
        let canvas = ZegoCanvas(view: view)
        engine?.startPreview(canvas)
        engine?.startPublishingStream(streamId)
    }

    func startPlaying(streamId: String, view: UIView) {
        let canvas = ZegoCanvas(view: view)
        engine?.startPlayingStream(streamId, canvas: canvas)
    }

    func endCall(roomId: String) {
        engine?.logoutRoom(roomId)
    }

    func destroy() {
        ZegoExpressEngine.destroy(nil)
    }
}

extension ZegoManager: ZegoEventHandler {
    func onRoomUserUpdate(_ updateType: ZegoUpdateType,
                         userList: [ZegoUser],
                         roomID: String) {
        // Handle user updates
    }

    func onRoomStreamUpdate(_ updateType: ZegoUpdateType,
                           streamList: [ZegoStream],
                           extendedData: [AnyHashable : Any]?,
                           roomID: String) {
        // Handle stream updates
        if updateType == .add {
            for stream in streamList {
                // Auto-play remote stream
            }
        }
    }
}
```

#### 3. Video Call View Controller

```swift
// VideoCallViewController.swift
class VideoCallViewController: UIViewController {

    var zegoManager: ZegoManager!
    var localView: UIView!
    var remoteView: UIView!
    var callId: String!
    var zegoConfig: ZegoConfig!

    override func viewDidLoad() {
        super.viewDidLoad()

        setupViews()
        setupCall()
    }

    func setupCall() {
        zegoManager = ZegoManager()
        zegoManager.initEngine(
            appId: UInt32(zegoConfig.app_id)!,
            appSign: zegoConfig.server_secret
        )

        zegoManager.loginRoom(
            roomId: zegoConfig.room_id,
            userId: zegoConfig.user_id,
            userName: zegoConfig.user_name
        )

        zegoManager.startPublishing(
            streamId: "stream_\(zegoConfig.user_id)",
            view: localView
        )
    }

    @objc func endCall() {
        CallAPIService.shared.endCall(callId: callId) { result in
            DispatchQueue.main.async {
                self.zegoManager.destroy()
                self.dismiss(animated: true)
            }
        }
    }
}
```

---

## Push Notifications

### Android (FCM)

#### 1. Firebase Service

```kotlin
// MyFirebaseMessagingService.kt
class MyFirebaseMessagingService : FirebaseMessagingService() {

    override fun onMessageReceived(remoteMessage: RemoteMessage) {
        val data = remoteMessage.data

        when (data["notification_type"]) {
            "incoming_call" -> showIncomingCallNotification(data)
            "call_ended" -> handleCallEnded(data)
            "call_rejected" -> handleCallRejected(data)
        }
    }

    private fun showIncomingCallNotification(data: Map<String, String>) {
        val callId = data["call_id"]
        val callerName = data["caller_name"]
        val callType = data["call_type"]

        val intent = Intent(this, IncomingCallActivity::class.java).apply {
            putExtra("call_id", callId)
            putExtra("caller_name", callerName)
            putExtra("call_type", callType)
            flags = Intent.FLAG_ACTIVITY_NEW_TASK
        }

        startActivity(intent)
    }

    override fun onNewToken(token: String) {
        // Register token with server
        lifecycleScope.launch {
            RetrofitClient.instance.registerDevice(
                RegisterDeviceRequest(token, "android")
            )
        }
    }
}
```

### iOS (APNs)

#### 1. Request Permissions

```swift
// AppDelegate.swift
func application(_ application: UIApplication,
                didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?) -> Bool {

    UNUserNotificationCenter.current().requestAuthorization(options: [.alert, .sound, .badge]) { granted, _ in
        if granted {
            DispatchQueue.main.async {
                application.registerForRemoteNotifications()
            }
        }
    }

    UNUserNotificationCenter.current().delegate = self
    return true
}

func application(_ application: UIApplication,
                didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data) {
    let token = deviceToken.map { String(format: "%02.2hhx", $0) }.joined()

    // Register with server
    CallAPIService.shared.registerDevice(deviceToken: token, platform: "ios") { result in
        print("Device registered")
    }
}
```

#### 2. Handle Notifications

```swift
extension AppDelegate: UNUserNotificationCenterDelegate {

    func userNotificationCenter(_ center: UNUserNotificationCenter,
                               willPresent notification: UNNotification,
                               withCompletionHandler completionHandler: @escaping (UNNotificationPresentationOptions) -> Void) {

        let userInfo = notification.request.content.userInfo

        if let notificationType = userInfo["notification_type"] as? String {
            if notificationType == "incoming_call" {
                showIncomingCallScreen(userInfo)
            }
        }

        completionHandler([.banner, .sound])
    }
}
```

---

## WebSocket Integration

### Android

```kotlin
import io.socket.client.IO
import io.socket.client.Socket

class WebSocketManager(private val userId: Int, private val token: String) {

    private var socket: Socket? = null

    fun connect() {
        val opts = IO.Options()
        opts.auth = mapOf("token" to token)

        socket = IO.socket("https://yourdomain.com", opts)

        socket?.on("private-user.$userId") { args ->
            val data = args[0] as JSONObject
            handleEvent(data)
        }

        socket?.connect()
    }

    private fun handleEvent(data: JSONObject) {
        when (data.getString("event")) {
            "call.initiated" -> onIncomingCall(data)
            "call.accepted" -> onCallAccepted(data)
            "call.rejected" -> onCallRejected(data)
            "call.ended" -> onCallEnded(data)
        }
    }

    fun disconnect() {
        socket?.disconnect()
    }
}
```

### iOS

```swift
import Starscream

class WebSocketManager: WebSocketDelegate {

    var socket: WebSocket!
    let userId: Int
    let token: String

    init(userId: Int, token: String) {
        self.userId = userId
        self.token = token
    }

    func connect() {
        var request = URLRequest(url: URL(string: "wss://yourdomain.com")!)
        request.timeoutInterval = 5
        socket = WebSocket(request: request)
        socket.delegate = self
        socket.connect()
    }

    func didReceive(event: WebSocketEvent, client: WebSocket) {
        switch event {
        case .text(let string):
            handleMessage(string)
        default:
            break
        }
    }

    func handleMessage(_ message: String) {
        guard let data = message.data(using: .utf8),
              let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
              let eventType = json["event"] as? String else {
            return
        }

        switch eventType {
        case "call.initiated":
            onIncomingCall(json)
        case "call.accepted":
            onCallAccepted(json)
        case "call.rejected":
            onCallRejected(json)
        case "call.ended":
            onCallEnded(json)
        default:
            break
        }
    }
}
```

---

## Call Flow

### Complete Call Flow

```
1. USER A (Web) initiates call
   ↓
2. Backend creates call record
   ↓
3. Push notification sent to USER B (Mobile)
   ↓
4. USER B receives notification & WebSocket event
   ↓
5. USER B accepts call
   ↓
6. Backend updates call status
   ↓
7. WebSocket event sent to USER A
   ↓
8. Both users join ZegoCloud room
   ↓
9. Audio/Video streaming begins
   ↓
10. Either user ends call
    ↓
11. Backend updates call record
    ↓
12. Other user notified via WebSocket & Push
```

---

## Troubleshooting

### Common Issues

#### 1. Authentication Fails
- Verify token is correctly stored and sent
- Check token expiration
- Ensure proper Authorization header format

#### 2. Push Notifications Not Received
- Verify device token registration
- Check FCM/APNs credentials on backend
- Ensure app has notification permissions

#### 3. WebSocket Connection Fails
- Verify WebSocket URL
- Check authentication token
- Ensure proper SSL/TLS configuration

#### 4. ZegoCloud Connection Fails
- Verify App ID and Server Secret
- Check network connectivity
- Ensure proper permissions (camera/microphone)

---

## Support

For issues or questions:
- 📧 Email: your.email@example.com
- 🐛 GitHub Issues: https://github.com/yourusername/zego-audio-video-calling/issues

---

Made with ❤️ for Mobile Developers
