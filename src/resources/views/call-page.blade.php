<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ucfirst($callType) }} Call - ZegoCloud</title>
    <link rel="stylesheet" href="{{ asset('vendor/zego-calling/css/call-page.css') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #1a1a1a;
            color: white;
            overflow: hidden;
        }

        #root {
            width: 100vw;
            height: 100vh;
            position: relative;
        }

        .call-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .video-container {
            flex: 1;
            position: relative;
            background: #000;
        }

        #local-video {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 200px;
            height: 150px;
            border-radius: 12px;
            overflow: hidden;
            background: #2a2a2a;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        #remote-video {
            width: 100%;
            height: 100%;
        }

        .call-controls {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            z-index: 20;
        }

        .control-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .control-btn:hover {
            transform: scale(1.1);
        }

        .mute-btn {
            background: #4a5568;
            color: white;
        }

        .mute-btn.active {
            background: #e53e3e;
        }

        .camera-btn {
            background: #4a5568;
            color: white;
        }

        .camera-btn.active {
            background: #e53e3e;
        }

        .end-call-btn {
            background: #e53e3e;
            color: white;
        }

        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-left-color: #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .user-info {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.6);
            padding: 12px 20px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .call-duration {
            font-size: 14px;
            color: #e0e0e0;
        }
    </style>
</head>
<body>
    <div id="root">
        <div class="call-container">
            <div class="video-container">
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Connecting to call...</p>
                </div>

                <div class="user-info" id="user-info" style="display: none;">
                    <div class="call-duration" id="call-duration">00:00</div>
                </div>

                <div id="remote-video"></div>
                <div id="local-video"></div>

                <div class="call-controls">
                    @if($callType === 'video')
                    <button class="control-btn camera-btn" id="camera-btn" title="Toggle Camera">
                        📹
                    </button>
                    @endif

                    <button class="control-btn mute-btn" id="mute-btn" title="Toggle Microphone">
                        🎤
                    </button>

                    <button class="control-btn end-call-btn" id="end-call-btn" title="End Call">
                        📞
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/@zegocloud/zego-express-engine-webrtc/index.js"></script>
    <script>
        const config = {
            appId: {{ $appId }},
            roomID: "{{ $roomID }}",
            userID: "{{ $user->id }}",
            userName: "{{ $user->name }}",
            callType: "{{ $callType }}",
            csrfToken: document.querySelector('meta[name="csrf-token"]').content
        };
    </script>
    <script src="{{ asset('vendor/zego-calling/js/call-page.js') }}"></script>
</body>
</html>
