<?php

namespace App\Notifications;

use App\Models\AcademicYear;
use App\Models\Proposal;
use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProposalStatusNotification extends Notification
{
    use Queueable;

    protected $proposal;
    protected $team;
    protected $status;
    protected $academicYear;

    public function __construct(Proposal $proposal, Team $team, string $status)
    {
        $this->proposal = $proposal;
        $this->team = $team;
        $this->status = $status;
        $this->academicYear = AcademicYear::where('is_active', true)->first();
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'proposal_' . $this->status,
            'proposal_id' => $this->proposal->id,
            'proposal_title' => $this->proposal->title,
            'team_id' => $this->team->id,
            'team_name' => $this->team->name ?? "Team {$this->team->id}",
            'message' => $this->status === 'approved' 
                ? "Your project idea '{$this->proposal->title}' has been approved!"
                : "Your project idea '{$this->proposal->title}' has been rejected.",
            'icon' => $this->status === 'approved' ? 'check-circle' : 'x-circle',
            'color' => $this->status === 'approved' ? 'green' : 'red',
            'academic_year_id' => $this->academicYear?->id,
            'created_at' => now(),
        ];
    }
}