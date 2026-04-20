<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get icon based on notification type
     */
    public function getIconAttribute()
    {
        return match($this->type) {
            'form_pending' => '📋',
            'ttd_request' => '✍️',
            'deadline_3days' => '⏰',
            'deadline_1day' => '🚨',
            'form_completed' => '✅',
            'form_rejected' => '❌',
            'assignment' => '👤',
            default => '🔔',
        };
    }

    /**
     * Get color class based on notification type
     */
    public function getColorClassAttribute()
    {
        return match($this->type) {
            'form_pending' => 'bg-blue-50 border-blue-400',
            'ttd_request' => 'bg-indigo-50 border-indigo-400',
            'deadline_3days' => 'bg-yellow-50 border-yellow-400',
            'deadline_1day' => 'bg-red-50 border-red-400',
            'form_completed' => 'bg-green-50 border-green-400',
            'form_rejected' => 'bg-red-50 border-red-400',
            'assignment' => 'bg-purple-50 border-purple-400',
            default => 'bg-gray-50 border-gray-400',
        };
    }
}
