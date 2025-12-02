<?php

namespace ZegoAudioVideoCalling\Services;

class ZegoCloudService
{
    /**
     * Get ZegoCloud App ID
     */
    public function getAppId(): ?string
    {
        return config('zego-calling.zegocloud.app_id');
    }

    /**
     * Get ZegoCloud Server Secret
     */
    public function getServerSecret(): ?string
    {
        return config('zego-calling.zegocloud.server_secret');
    }

    /**
     * Generate ZegoCloud configuration for a user
     */
    public function generateConfig(string $roomId, int $userId, string $userName): array
    {
        return [
            'app_id' => $this->getAppId(),
            'server_secret' => $this->getServerSecret(),
            'room_id' => $roomId,
            'user_id' => (string)$userId,
            'user_name' => $userName,
        ];
    }

    /**
     * Generate a unique room ID
     */
    public function generateRoomId(): string
    {
        return 'room_' . \Illuminate\Support\Str::random(20) . '_' . time();
    }

    /**
     * Validate ZegoCloud credentials
     */
    public function validateCredentials(): bool
    {
        return !empty($this->getAppId()) && !empty($this->getServerSecret());
    }
}
