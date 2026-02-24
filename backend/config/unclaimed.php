<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Unclaimed Laundry Reminder Intervals (in days)
    |--------------------------------------------------------------------------
    */
    'reminder_days' => [
        1 => 'first_reminder',      // Day 1: Gentle reminder
        3 => 'second_reminder',     // Day 3: Follow-up
        7 => 'urgent_reminder',     // Day 7: Urgent notice
        14 => 'final_notice',       // Day 14: Final notice before disposal
    ],

    /*
    |--------------------------------------------------------------------------
    | Disposal Policy
    |--------------------------------------------------------------------------
    */
    'disposal_after_days' => 30,    // Days before laundry can be disposed
    'storage_fee_per_day' => 10,    // ₱10 per day storage fee after 7 days

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notify_admin_after_days' => 3, // Notify admin when unclaimed for 3+ days
    'send_sms_reminders' => false,  // Enable SMS reminders (future feature)
];
