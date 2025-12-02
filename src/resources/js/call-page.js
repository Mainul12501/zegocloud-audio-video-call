/**
 * ZegoCloud Call Page JavaScript
 * Handles audio/video calling functionality
 */

class ZegoCallManager {
    constructor(config) {
        this.config = config;
        this.zg = null;
        this.localStream = null;
        this.remoteStream = null;
        this.isMuted = false;
        this.isCameraOff = false;
        this.callStartTime = null;
        this.durationInterval = null;

        this.init();
    }

    async init() {
        try {
            // Initialize ZegoCloud Engine
            this.zg = new ZegoExpressEngine(this.config.appId, 'wss://webliveroom-api.zegocloud.com/ws');

            // Set event listeners
            this.setupEventListeners();

            // Login to room
            await this.loginRoom();

            // Start publishing and playing streams
            await this.startCall();

            // Hide loading indicator
            document.getElementById('loading').style.display = 'none';
            document.getElementById('user-info').style.display = 'block';

            // Start call duration timer
            this.startCallDuration();

        } catch (error) {
            console.error('Failed to initialize call:', error);
            alert('Failed to connect to the call. Please try again.');
            window.close();
        }
    }

    setupEventListeners() {
        // Camera toggle
        const cameraBtn = document.getElementById('camera-btn');
        if (cameraBtn) {
            cameraBtn.addEventListener('click', () => this.toggleCamera());
        }

        // Microphone toggle
        const muteBtn = document.getElementById('mute-btn');
        if (muteBtn) {
            muteBtn.addEventListener('click', () => this.toggleMute());
        }

        // End call
        document.getElementById('end-call-btn').addEventListener('click', () => this.endCall());

        // Listen for remote stream updates
        this.zg.on('roomStreamUpdate', async (roomID, updateType, streamList) => {
            if (updateType === 'ADD') {
                for (let stream of streamList) {
                    await this.playRemoteStream(stream.streamID);
                }
            } else if (updateType === 'DELETE') {
                console.log('Remote stream removed:', streamList);
                this.handleRemoteStreamRemoved();
            }
        });

        // Handle errors
        this.zg.on('error', (error) => {
            console.error('ZegoCloud error:', error);
        });
    }

    async loginRoom() {
        const token = ''; // For production, implement token generation
        const user = {
            userID: this.config.userID,
            userName: this.config.userName
        };

        try {
            const result = await this.zg.loginRoom(
                this.config.roomID,
                token,
                user,
                { userUpdate: true }
            );
            console.log('Logged in to room:', result);
        } catch (error) {
            console.error('Failed to login to room:', error);
            throw error;
        }
    }

    async startCall() {
        try {
            // Create local stream
            const streamConfig = {
                camera: {
                    audio: true,
                    video: this.config.callType === 'video'
                }
            };

            this.localStream = await this.zg.createStream(streamConfig);

            // Play local stream
            const localVideo = document.getElementById('local-video');
            const localVideoElement = this.zg.createLocalStreamView(this.localStream);
            localVideoElement.style.width = '100%';
            localVideoElement.style.height = '100%';
            localVideoElement.style.objectFit = 'cover';
            localVideo.appendChild(localVideoElement);

            // Start publishing
            const streamID = 'stream_' + this.config.userID;
            await this.zg.startPublishingStream(streamID, this.localStream);
            console.log('Publishing stream:', streamID);

        } catch (error) {
            console.error('Failed to start call:', error);
            throw error;
        }
    }

    async playRemoteStream(streamID) {
        try {
            const remoteVideo = document.getElementById('remote-video');
            this.remoteStream = await this.zg.startPlayingStream(streamID);

            const remoteVideoElement = this.zg.createRemoteStreamView(this.remoteStream);
            remoteVideoElement.style.width = '100%';
            remoteVideoElement.style.height = '100%';
            remoteVideoElement.style.objectFit = 'contain';

            remoteVideo.innerHTML = '';
            remoteVideo.appendChild(remoteVideoElement);

            console.log('Playing remote stream:', streamID);
        } catch (error) {
            console.error('Failed to play remote stream:', error);
        }
    }

    handleRemoteStreamRemoved() {
        const remoteVideo = document.getElementById('remote-video');
        remoteVideo.innerHTML = '<p style="color: white; text-align: center; padding: 50px;">Other user disconnected</p>';
    }

    toggleMute() {
        this.isMuted = !this.isMuted;
        const muteBtn = document.getElementById('mute-btn');

        if (this.localStream) {
            this.zg.mutePublishStreamAudio(this.localStream, this.isMuted);
        }

        if (this.isMuted) {
            muteBtn.classList.add('active');
            muteBtn.innerHTML = '🔇';
        } else {
            muteBtn.classList.remove('active');
            muteBtn.innerHTML = '🎤';
        }
    }

    toggleCamera() {
        this.isCameraOff = !this.isCameraOff;
        const cameraBtn = document.getElementById('camera-btn');

        if (this.localStream) {
            this.zg.mutePublishStreamVideo(this.localStream, this.isCameraOff);
        }

        if (this.isCameraOff) {
            cameraBtn.classList.add('active');
            cameraBtn.innerHTML = '📹';
        } else {
            cameraBtn.classList.remove('active');
            cameraBtn.innerHTML = '📹';
        }
    }

    async endCall() {
        try {
            // Stop publishing
            if (this.localStream) {
                await this.zg.stopPublishingStream('stream_' + this.config.userID);
                this.zg.destroyStream(this.localStream);
            }

            // Logout from room
            await this.zg.logoutRoom(this.config.roomID);

            // Stop call duration timer
            if (this.durationInterval) {
                clearInterval(this.durationInterval);
            }

            // Notify server about call end
            await this.notifyServerCallEnded();

            // Close window or redirect
            window.close();
            if (!window.closed) {
                window.location.href = '/';
            }

        } catch (error) {
            console.error('Error ending call:', error);
            window.close();
        }
    }

    async notifyServerCallEnded() {
        try {
            const callId = new URLSearchParams(window.location.search).get('callId');
            if (callId) {
                await fetch(`/call/${callId}/end`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken
                    }
                });
            }
        } catch (error) {
            console.error('Failed to notify server:', error);
        }
    }

    startCallDuration() {
        this.callStartTime = new Date();
        const durationElement = document.getElementById('call-duration');

        this.durationInterval = setInterval(() => {
            const now = new Date();
            const diff = Math.floor((now - this.callStartTime) / 1000);
            const minutes = Math.floor(diff / 60);
            const seconds = diff % 60;

            durationElement.textContent =
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    }
}

// Initialize call manager when page loads
document.addEventListener('DOMContentLoaded', () => {
    if (typeof config !== 'undefined') {
        window.callManager = new ZegoCallManager(config);
    } else {
        console.error('Configuration not found');
        alert('Call configuration error. Please refresh the page.');
    }
});
