<?php

namespace ZegoAudioVideoCalling\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ZegoAudioVideoCalling\Events\CallAccepted;
use ZegoAudioVideoCalling\Events\CallEnded;
use ZegoAudioVideoCalling\Events\CallInitiated;
use ZegoAudioVideoCalling\Events\CallRejected;
use ZegoAudioVideoCalling\Models\Call;
use ZegoAudioVideoCalling\Services\PushNotificationService;
use ZegoAudioVideoCalling\Services\ZegoCloudService;

class MobileApiController extends Controller
{
    protected $pushNotificationService;
    protected $zegoService;

    public function __construct(
        PushNotificationService $pushNotificationService,
        ZegoCloudService $zegoService
    ) {
        $this->pushNotificationService = $pushNotificationService;
        $this->zegoService = $zegoService;
    }

    /**
     * Register or update device token for push notifications
     */
    public function registerDevice(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'device_platform' => 'required|in:ios,android',
        ]);

        $user = Auth::user();

        $user->update([
            'device_token' => $request->device_token,
            'device_platform' => $request->device_platform,
            'is_online' => true,
            'last_seen' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'device_platform' => $user->device_platform,
                'is_online' => $user->is_online,
            ]
        ]);
    }

    /**
     * Update user online status
     */
    public function updateOnlineStatus(Request $request)
    {
        $request->validate([
            'is_online' => 'required|boolean',
        ]);

        $user = Auth::user();
        $user->update([
            'is_online' => $request->is_online,
            'last_seen' => now(),
        ]);

        return response()->json([
            'success' => true,
            'is_online' => $user->is_online,
            'last_seen' => $user->last_seen,
        ]);
    }

    /**
     * Initiate a call
     */
    public function initiateCall(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'call_type' => 'required|in:audio,video',
        ]);

        $caller = Auth::user();
        $userModel = config('zego-calling.user_model', 'App\Models\User');
        $receiver = $userModel::findOrFail($request->receiver_id);

        if ($caller->id === $receiver->id) {
            return response()->json(['error' => 'You cannot call yourself'], 400);
        }

        $roomId = $this->zegoService->generateRoomId();

        $call = Call::create([
            'caller_id' => $caller->id,
            'receiver_id' => $receiver->id,
            'room_id' => $roomId,
            'call_type' => $request->call_type,
            'status' => 'initiated',
        ]);

        if (config('zego-calling.broadcasting.enabled')) {
            broadcast(new CallInitiated($call))->toOthers();
        }

        if ($receiver->device_token && config('zego-calling.push_notifications.enabled')) {
            $this->pushNotificationService->sendCallNotification(
                $receiver,
                $caller,
                $call,
                'incoming'
            );
        }

        return response()->json([
            'success' => true,
            'call' => $call->load('caller', 'receiver'),
            'room_id' => $roomId,
            'zegocloud_config' => $this->zegoService->generateConfig($roomId, $caller->id, $caller->name)
        ]);
    }

    /**
     * Accept a call
     */
    public function acceptCall(Request $request, $callId)
    {
        $call = Call::findOrFail($callId);
        $user = Auth::user();

        if ($call->receiver_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($call->status !== 'initiated') {
            return response()->json(['error' => 'Call is not available'], 400);
        }

        $call->update([
            'status' => 'accepted',
            'started_at' => now(),
        ]);

        if (config('zego-calling.broadcasting.enabled')) {
            broadcast(new CallAccepted($call))->toOthers();
        }

        return response()->json([
            'success' => true,
            'call' => $call->load('caller', 'receiver'),
            'zegocloud_config' => $this->zegoService->generateConfig(
                $call->room_id,
                $user->id,
                $user->name
            )
        ]);
    }

    /**
     * Reject a call
     */
    public function rejectCall(Request $request, $callId)
    {
        $call = Call::findOrFail($callId);
        $user = Auth::user();

        if ($call->receiver_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!in_array($call->status, ['initiated', 'ringing'])) {
            return response()->json(['error' => 'Call cannot be rejected'], 400);
        }

        $call->update([
            'status' => 'rejected',
            'ended_at' => now(),
        ]);

        if (config('zego-calling.broadcasting.enabled')) {
            broadcast(new CallRejected($call))->toOthers();
        }

        $caller = $call->caller;
        if ($caller->device_token && config('zego-calling.push_notifications.enabled')) {
            $this->pushNotificationService->sendCallNotification(
                $caller,
                $user,
                $call,
                'rejected'
            );
        }

        return response()->json(['success' => true, 'message' => 'Call rejected']);
    }

    /**
     * End an active call
     */
    public function endCall(Request $request, $callId)
    {
        $call = Call::findOrFail($callId);
        $user = Auth::user();

        if (!in_array($user->id, [$call->caller_id, $call->receiver_id])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($call->status === 'ended') {
            return response()->json(['success' => true, 'message' => 'Call already ended']);
        }

        $duration = null;
        if ($call->started_at) {
            $duration = now()->diffInSeconds($call->started_at);
        }

        $call->update([
            'status' => 'ended',
            'ended_at' => now(),
            'duration' => $duration,
        ]);

        $targetUserId = $user->id === $call->caller_id ? $call->receiver_id : $call->caller_id;

        if (config('zego-calling.broadcasting.enabled')) {
            broadcast(new CallEnded($call, $targetUserId))->toOthers();
        }

        $userModel = config('zego-calling.user_model', 'App\Models\User');
        $targetUser = $userModel::find($targetUserId);
        if ($targetUser && $targetUser->device_token && config('zego-calling.push_notifications.enabled')) {
            $this->pushNotificationService->sendCallNotification(
                $targetUser,
                $user,
                $call,
                'ended'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Call ended',
            'duration' => $duration
        ]);
    }

    /**
     * Get active calls
     */
    public function getActiveCalls()
    {
        $user = Auth::user();

        $activeCalls = Call::forUser($user->id)
            ->active()
            ->with(['caller', 'receiver'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'active_calls' => $activeCalls
        ]);
    }

    /**
     * Get call history
     */
    public function getCallHistory(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 20);

        $callHistory = Call::forUser($user->id)
            ->with(['caller', 'receiver'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'call_history' => $callHistory
        ]);
    }

    /**
     * Get call details
     */
    public function getCallDetails($callId)
    {
        $call = Call::with(['caller', 'receiver'])->findOrFail($callId);
        $user = Auth::user();

        if (!in_array($user->id, [$call->caller_id, $call->receiver_id])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'call' => $call
        ]);
    }

    /**
     * Check user availability
     */
    public function checkUserAvailability($userId)
    {
        $userModel = config('zego-calling.user_model', 'App\Models\User');
        $targetUser = $userModel::findOrFail($userId);

        $hasActiveCall = Call::forUser($targetUser->id)
            ->active()
            ->exists();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'is_online' => $targetUser->is_online ?? false,
                'last_seen' => $targetUser->last_seen ?? null,
            ],
            'is_available' => !$hasActiveCall && ($targetUser->is_online ?? false),
            'has_active_call' => $hasActiveCall,
        ]);
    }

    /**
     * Generate ZegoCloud token
     */
    public function generateToken(Request $request)
    {
        $request->validate([
            'room_id' => 'required|string',
        ]);

        $user = Auth::user();

        return response()->json([
            'success' => true,
            'zegocloud_config' => $this->zegoService->generateConfig(
                $request->room_id,
                $user->id,
                $user->name
            )
        ]);
    }
}
