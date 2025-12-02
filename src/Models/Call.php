<?php

namespace ZegoAudioVideoCalling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    protected $fillable = [
        'caller_id',
        'receiver_id',
        'room_id',
        'call_type',
        'status',
        'started_at',
        'ended_at',
        'duration',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('zego-calling.database.calls_table', 'calls');
    }

    public function caller(): BelongsTo
    {
        $userModel = config('zego-calling.user_model', 'App\Models\User');
        return $this->belongsTo($userModel, 'caller_id');
    }

    public function receiver(): BelongsTo
    {
        $userModel = config('zego-calling.user_model', 'App\Models\User');
        return $this->belongsTo($userModel, 'receiver_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['initiated', 'ringing', 'accepted']);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['ended', 'rejected', 'missed']);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('caller_id', $userId)
              ->orWhere('receiver_id', $userId);
        });
    }
}
