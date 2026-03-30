<?php

namespace App\Observers;

use App\Models\AcademicYear;
use App\Models\GraduationProject;
use App\Models\Proposal;

class ProposalObserver
{
    /**
     * Handle the Proposal "created" event.
     */
 public function created(Proposal $proposal): void
    {
        $this->createGraduationProjectIfApproved($proposal);
    }

        /**
     * Handle the Proposal "updated" event.
     */
    public function updated(Proposal $proposal): void
    {
        if ($proposal->wasChanged('status')) {
            $this->createGraduationProjectIfApproved($proposal);
        }
    }

    protected function createGraduationProjectIfApproved(Proposal $proposal): void
    {
        if ($proposal->status !== 'approved') {
            return;
        }

        $academicYear = AcademicYear::where('is_active', true)->first();

        if (!$academicYear) {
            return;
        }

        GraduationProject::firstOrCreate(
            ['proposal_id' => $proposal->id],
            [
                'academic_year_id' => $academicYear->id,
                'team_id' => $proposal->team_id,
                'image_url' => $proposal->image_url,
            ]
        );
    }


  

   
}
