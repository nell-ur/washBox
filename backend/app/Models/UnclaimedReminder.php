<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnclaimedReminder extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'unclaimed_laundry_id',
        'reminder_day',
        'notification_id',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function unclaimedLaundry(): BelongsTo
    {
        return $this->belongsTo(UnclaimedLaundry::class);
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
