<?php

namespace App\Console\Commands;

use App\Models\Laundry;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendUnclaimedReminders extends Command
{
    /**
     * The name and signature of the console command.
     * Run manually: php artisan unclaimed:remind
     * Run for specific day: php artisan unclaimed:remind --day=7
     * Dry run (no actual sending): php artisan unclaimed:remind --dry-run
     */
    protected $signature = 'unclaimed:remind
                            {--day= : Only send for a specific day threshold (3, 5, or 7)}
                            {--dry-run : Preview what would be sent without actually sending}';

    protected $description = 'Send FCM push reminders to customers with unclaimed laundry (Day 3, 5, 7)';

    public function __construct(protected NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $specificDay = $this->option('day') ? (int) $this->option('day') : null;

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE — no notifications will be sent');
        }

        $this->info('📦 WashBox Unclaimed Laundry Reminder System');
        $this->info('Running at: ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();

        // Define reminder thresholds: day => urgency label
        $thresholds = [
            3 => 'second',  // Day 3 — friendly reminder
            5 => 'urgent',  // Day 5 — urgent
            7 => 'final',   // Day 7 — final notice
        ];

        // If --day was passed, only process that threshold
        if ($specificDay !== null) {
            if (!isset($thresholds[$specificDay])) {
                $this->error("Invalid --day value. Use 3, 5, or 7.");
                return self::FAILURE;
            }
            $thresholds = [$specificDay => $thresholds[$specificDay]];
        }

        $totalSent    = 0;
        $totalSkipped = 0;
        $totalFailed  = 0;

        foreach ($thresholds as $day => $urgency) {
            $this->info("📅 Processing Day {$day} reminders ({$urgency})...");

            // Find laundries that have been ready for EXACTLY this day window
            // e.g. day=3 means ready_at is between 3 and 4 days ago
            $laundries = Laundry::with(['customer', 'branch'])
                ->where('status', 'ready')
                ->whereNotNull('ready_at')
                ->where('ready_at', '<=', now()->subDays($day))
                ->where('ready_at', '>', now()->subDays($day + 1))
                ->get();

            if ($laundries->isEmpty()) {
                $this->line("   No laundries at Day {$day} threshold.");
                continue;
            }

            $this->line("   Found {$laundries->count()} laundry/laundries at Day {$day}.");

            $messages = [
                'second' => [
                    'title' => '📦 Reminder: Laundry Ready',
                    'body'  => 'Day 3: Your laundry is still waiting at {branch}. Please pick it up at your earliest convenience. Tracking #: {tracking}',
                ],
                'urgent' => [
                    'title' => '⚠️ Urgent: Unclaimed Laundry',
                    'body'  => 'Day 5: Your laundry at {branch} is still unclaimed. Storage fees may apply after Day 7. Tracking #: {tracking}',
                ],
                'final' => [
                    'title' => '🚨 Final Notice — Action Required',
                    'body'  => 'Day 7: FINAL REMINDER — Your laundry at {branch} must be claimed soon or it will be subject to disposal. Tracking #: {tracking}',
                ],
            ];

            $template = $messages[$urgency];

            foreach ($laundries as $laundry) {
                $customer = $laundry->customer;
                $branchName = $laundry->branch->name ?? 'WashBox';
                $tracking = $laundry->tracking_number ?? "#{$laundry->id}";

                $title = $template['title'];
                $body  = str_replace(
                    ['{branch}', '{tracking}'],
                    [$branchName, $tracking],
                    $template['body']
                );

                // Skip if customer has no FCM token
                if (empty($customer?->fcm_token)) {
                    $this->line("   ⚪ Skipped  #{$laundry->id} ({$customer?->name}) — no FCM token");
                    $totalSkipped++;

                    // Still create DB notification record
                    if (!$isDryRun) {
                        try {
                            Notification::createUnclaimedReminder($laundry, $day, $urgency);
                            $laundry->recordReminderSent();
                        } catch (\Exception $e) {
                            Log::warning("Failed to record DB notification for laundry #{$laundry->id}: " . $e->getMessage());
                        }
                    }
                    continue;
                }

                if ($isDryRun) {
                    $this->line("   🔍 Would send to: {$customer->name} ({$customer->phone})");
                    $this->line("      Title: {$title}");
                    $this->line("      Body:  {$body}");
                    $totalSent++;
                    continue;
                }

                try {
                    // Send FCM push
                    $this->notificationService->sendToCustomer(
                        $customer,
                        $title,
                        $body,
                        [
                            'laundries_id'      => (string) $laundry->id,
                            'status'          => 'ready',
                            'type'            => 'unclaimed_reminder',
                            'days'            => (string) $day,
                            'tracking_number' => $tracking,
                        ]
                    );

                    // Record in DB
                    Notification::createUnclaimedReminder($laundry, $day, $urgency);
                    $laundry->recordReminderSent();

                    // Log to status history
                    $laundry->statusHistories()->create([
                        'status'     => 'ready',
                        'changed_by' => null, // System-generated
                        'notes'      => "Auto reminder sent (Day {$day}, {$urgency}) via scheduler",
                    ]);

                    $this->line("   ✅ Sent    #{$laundry->id} → {$customer->name} ({$customer->phone})");
                    $totalSent++;

                } catch (\Exception $e) {
                    $this->error("   ❌ Failed  #{$laundry->id} → {$customer->name}: " . $e->getMessage());
                    Log::error("Unclaimed reminder failed for laundry #{$laundry->id}: " . $e->getMessage());
                    $totalFailed++;
                }
            }

            $this->newLine();
        }

        // Summary
        $this->info('────────────────────────────────');
        $this->info("✅ Sent:    {$totalSent}");
        $this->info("⚪ Skipped: {$totalSkipped} (no FCM token)");
        if ($totalFailed > 0) {
            $this->error("❌ Failed:  {$totalFailed}");
        }
        $this->info('────────────────────────────────');

        Log::info("Unclaimed reminders completed — Sent: {$totalSent}, Skipped: {$totalSkipped}, Failed: {$totalFailed}");

        return self::SUCCESS;
    }
}
