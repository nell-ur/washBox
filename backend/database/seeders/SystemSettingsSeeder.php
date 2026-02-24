<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ── GENERAL ──────────────────────────────────────────────
        SystemSetting::set('shop_name',         'WashBox Laundry Services', 'string',  'general');
        SystemSetting::set('contact_number',    '+63 912 345 6789',         'string',  'general');
        SystemSetting::set('business_email',    '',                         'string',  'general');
        SystemSetting::set('disposal_threshold_days', 30,                   'integer', 'general');

        // ── PRICING ───────────────────────────────────────────────
        SystemSetting::set('default_price_per_piece',      60,   'integer', 'pricing');
        SystemSetting::set('default_price_per_load',    120,  'integer', 'pricing');
        SystemSetting::set('minimum_order_amount',      100,  'integer', 'pricing');
        SystemSetting::set('storage_fee_per_day',       5,    'integer', 'pricing');
        SystemSetting::set('storage_grace_period_days', 3,    'integer', 'pricing');
        SystemSetting::set('vat_rate',                  0,    'integer', 'pricing');
        SystemSetting::set('vat_inclusive',             '1',  'boolean', 'pricing');

        // ── BUSINESS HOURS ────────────────────────────────────────
        $hours = [
            'monday'    => ['07:00', '20:00', true],
            'tuesday'   => ['07:00', '20:00', true],
            'wednesday' => ['07:00', '20:00', true],
            'thursday'  => ['07:00', '20:00', true],
            'friday'    => ['07:00', '20:00', true],
            'saturday'  => ['08:00', '18:00', true],
            'sunday'    => ['08:00', '14:00', false],
        ];

        foreach ($hours as $day => [$open, $close, $isOpen]) {
            SystemSetting::set("hours_{$day}_start", $open,              'string',  'hours');
            SystemSetting::set("hours_{$day}_end",   $close,             'string',  'hours');
            SystemSetting::set("hours_{$day}_open",  $isOpen ? '1' : '0','boolean', 'hours');
        }

        // ── PICKUP & DELIVERY ─────────────────────────────────────
        SystemSetting::set('enable_pickup',           '1',  'boolean', 'pickup');
        SystemSetting::set('enable_delivery',         '1',  'boolean', 'pickup');
        SystemSetting::set('default_pickup_fee',      50,   'integer', 'pickup');
        SystemSetting::set('default_delivery_fee',    50,   'integer', 'pickup');
        SystemSetting::set('max_service_radius_km',   10,   'integer', 'pickup');
        SystemSetting::set('pickup_advance_days_min', 1,    'integer', 'pickup');
        SystemSetting::set('pickup_advance_days_max', 7,    'integer', 'pickup');

        // ── RECEIPT & INVOICE ─────────────────────────────────────
        SystemSetting::set('tracking_prefix',         'WB',                                                     'string',  'receipt');
        SystemSetting::set('receipt_header',          'Thank you for choosing WashBox Laundry Services!',       'string',  'receipt');
        SystemSetting::set('receipt_footer',          'Please keep this receipt. Claims without receipt may not be honored.', 'string', 'receipt');
        SystemSetting::set('receipt_claim_reminder',  'Please claim your laundry within 30 days.',              'string',  'receipt');
        SystemSetting::set('receipt_show_branch',     '1',                                                      'boolean', 'receipt');
        SystemSetting::set('receipt_show_staff',      '0',                                                      'boolean', 'receipt');

        // ── NOTIFICATIONS ─────────────────────────────────────────
        SystemSetting::set('enable_push_notifications', '1', 'boolean', 'notifications');
        SystemSetting::set('notify_laundry_received',   '1', 'boolean', 'notifications');
        SystemSetting::set('notify_laundry_ready',      '1', 'boolean', 'notifications');
        SystemSetting::set('notify_laundry_completed',  '1', 'boolean', 'notifications');
        SystemSetting::set('notify_unclaimed',          '1', 'boolean', 'notifications');
        SystemSetting::set('reminder_day_3',            '1', 'boolean', 'notifications');
        SystemSetting::set('reminder_day_5',            '1', 'boolean', 'notifications');
        SystemSetting::set('reminder_day_7',            '1', 'boolean', 'notifications');
        SystemSetting::set('fcm_server_key',            '',  'string',  'notifications');
        SystemSetting::set('fcm_sender_id',             '',  'string',  'notifications');
    }
}
