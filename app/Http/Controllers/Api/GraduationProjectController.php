<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GraduationProject;
use App\Models\Proposal;
use Illuminate\Http\Request;

class GraduationProjectController extends Controller
{
    public function store()
    {
        $academic_year=AcademicYear::where('is_active',true)->first();
        $proposals=Proposal::where('status','approved')->get();
        if(count($proposals)>0)
            {
                foreach($proposals as $proposal)
                    {
                        $project=GraduationProject::create([
                            'academic_year_id'=>$academic_year->id,
                            'team_id'=>$proposal->team_id,
                            'proposal_id'=>$proposal->id,
                            'image_url'=>$proposal->image_url
                        ]);
                    }
            }
    }
}
