<?php

namespace App\Console\Commands;

use App\Models\Milestone;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncMilestoneStatuses extends Command
{
    protected $signature = 'milestones:sync-statuses';
    protected $description = 'Sync milestone statuses based on dates and forced open flag';

    public function handle()
{
    $today = Carbon::today();

    $milestones =Milestone::where('is_active', true)->get();

    foreach ($milestones as $milestone) {
        if (!$milestone->start_date || !$milestone->deadline) {
            continue;
        }

        $startDate =Carbon::parse($milestone->start_date);
        $deadline = Carbon::parse($milestone->deadline);

        $newStatus = $milestone->status;
        $isOpen = $milestone->is_open;

        if ($milestone->is_forced_open) {
            $newStatus = 'on_progress';
            $isOpen = true;
        } elseif ($milestone->is_forced_closed) {
            $newStatus = 'completed';
            $isOpen = false;
        } elseif ($startDate <= $today && $deadline >= $today) {
            $newStatus = 'on_progress';
            $isOpen = true;
        } elseif ($deadline < $today) {
            $newStatus = 'completed';
            $isOpen = false;
        } elseif ($startDate > $today) {
            $newStatus = 'pending';
            $isOpen = false;
        }

        if (
            $milestone->status !== $newStatus ||
            (bool) $milestone->is_open !== (bool) $isOpen
        ) {
            $milestone->update([
                'status' => $newStatus,
                'is_open' => $isOpen,
            ]);
        }
    }

    $this->info('Milestone statuses synced successfully.');

    return self::SUCCESS;
}
}