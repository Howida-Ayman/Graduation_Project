<?php

namespace App\Notifications;

use App\Models\AcademicYear;
use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeamNoteNotification extends Notification
{
    use Queueable;

    protected $fromUser;
    protected $team;
    protected $note;

    public function __construct(User $fromUser, Team $team, string $note)
    {
        $this->fromUser = $fromUser;
        $this->team = $team;
        $this->note = $note;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        
        if (!$activeAcademicYear) {
            throw new \Exception('No active academic year found');
        }
        
        return [
            'academic_year_id' => $activeAcademicYear->id,
            'type' => 'team_note',
            'from_user_id' => $this->fromUser->id,
            'from_user_name' => $this->fromUser->full_name,
            'team_id' => $this->team->id,
            'team_name' => $this->team->name ?? "Team {$this->team->id}",
            'note' => $this->note,
            'message' => "{$this->fromUser->full_name} left a note",
            'icon' => 'message',
            'color' => 'gray',
            'created_at' => now(),
        ];
    }
}