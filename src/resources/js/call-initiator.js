/**
 * ZegoCloud Call Initiator
 * This script provides functions to initiate audio and video calls
 */

const ZegoCloudCaller = {
    /**
     * Initiate a call to another user
     * @param {number} receiverId - The ID of the user to call
     * @param {string} callType - 'audio' or 'video'
     * @param {string} csrfToken - CSRF token for the request
     */
    initiateCall: function(receiverId, callType = 'video', csrfToken) {
        if (!receiverId) {
            this.showNotification('Please select a user to call', 'error');
            return;
        }

        if (!['audio', 'video'].includes(callType)) {
            this.showNotification('Invalid call type', 'error');
            return;
        }

        this.showNotification(`Initiating ${callType} call...`, 'info');

        fetch('/call/initiate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                receiver_id: receiverId,
                call_type: callType
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.room_url + '&callId=' + data.call.id;
            } else {
                this.showNotification(data.error || 'Failed to initiate call', 'error');
            }
        })
        .catch(error => {
            console.error('Error initiating call:', error);
            this.showNotification('Failed to initiate call. Please try again.', 'error');
        });
    },

    /**
     * Create a video call button
     * @param {number} receiverId - The ID of the user to call
     * @param {string} csrfToken - CSRF token
     * @returns {HTMLButtonElement}
     */
    createVideoCallButton: function(receiverId, csrfToken) {
        const button = document.createElement('button');
        button.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M23 7l-7 5 7 5V7z"/>
                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
            </svg>
            Video Call
        `;
        button.className = 'zego-call-btn video-call-btn';
        button.onclick = () => this.initiateCall(receiverId, 'video', csrfToken);
        return button;
    },

    /**
     * Create an audio call button
     * @param {number} receiverId - The ID of the user to call
     * @param {string} csrfToken - CSRF token
     * @returns {HTMLButtonElement}
     */
    createAudioCallButton: function(receiverId, csrfToken) {
        const button = document.createElement('button');
        button.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
            </svg>
            Audio Call
        `;
        button.className = 'zego-call-btn audio-call-btn';
        button.onclick = () => this.initiateCall(receiverId, 'audio', csrfToken);
        return button;
    },

    /**
     * Show a notification message
     * @param {string} message - The message to display
     * @param {string} type - 'info', 'success', or 'error'
     */
    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = 'zego-notification zego-notification-' + type;
        notification.textContent = message;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            background: ${type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#3b82f6'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 100000;
            font-size: 14px;
            font-weight: 500;
            max-width: 400px;
            animation: slideInRight 0.3s ease-out;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
};

// Add default styles
if (!document.getElementById('zego-call-btn-styles')) {
    const style = document.createElement('style');
    style.id = 'zego-call-btn-styles';
    style.textContent = `
        .zego-call-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            color: white;
        }

        .zego-call-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .zego-call-btn:active {
            transform: translateY(0);
        }

        .video-call-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .audio-call-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        @media (max-width: 768px) {
            .zego-call-btn {
                padding: 8px 12px;
                font-size: 13px;
            }

            .zego-notification {
                left: 20px;
                right: 20px;
                top: 20px;
            }
        }
    `;
    document.head.appendChild(style);
}

// Make it available globally
window.ZegoCloudCaller = ZegoCloudCaller;
