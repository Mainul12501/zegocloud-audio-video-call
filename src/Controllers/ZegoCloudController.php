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
use ZegoAudioVideoCalling\Services\ZegoCloudService;

class ZegoCloudController extends Controller
{
    protected $zegoService;

    public function __construct(ZegoCloudService $zegoService)
    {
        $this->zegoService = $zegoService;
    }

    /**
     * View call page
     */
    public function viewCallPage(Request $request)
    {
        $user = Auth::user();
        $roomId = $request->query('roomID');
        $callType = $request->query('type', 'video');

        if (!$user) {
            return redirect()->route('login');
        }

        return view('zego-calling::call-page', [
            'user' => $user,
            'roomID' => $roomId,
            'callType' => $callType,
            'appId' => $this->zegoService->getAppId(),
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

        return response()->json([
            'success' => true,
            'call' => $call,
            'room_url' => route('zego-calling.call-page', [
                'roomID' => $roomId,
                'type' => $request->call_type,
                'chatWith' => $receiver->id
            ])
        ]);
    }

    /**
     * Accept a call
     */
    public function acceptCall(Request $request, Call $call)
    {
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
            'call' => $call,
            'room_url' => route('zego-calling.call-page', [
                'roomID' => $call->room_id,
                'type' => $call->call_type,
                'chatWith' => $call->caller_id
            ])
        ]);
    }

    /**
     * Reject a call
     */
    public function rejectCall(Request $request, Call $call)
    {
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

        return response()->json(['success' => true]);
    }

    /**
     * End a call
     */
    public function endCall(Request $request, Call $call)
    {
        $user = Auth::user();

        if (!in_array($user->id, [$call->caller_id, $call->receiver_id])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($call->status === 'ended') {
            return response()->json(['success' => true]);
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

        return response()->json(['success' => true]);
    }

    /**
     * Get call details
     */
    public function getCallDetails(Call $call)
    {
        $user = Auth::user();

        if (!in_array($user->id, [$call->caller_id, $call->receiver_id])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'call' => $call->load('caller', 'receiver')
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
            'user_id' => (string)$user->id,
            'user_name' => $user->name,
            'room_id' => $request->room_id,
            'app_id' => $this->zegoService->getAppId(),
        ]);
    }
}
