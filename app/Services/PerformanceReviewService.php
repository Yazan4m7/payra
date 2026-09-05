<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceReview;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PerformanceReviewService
{
    public function open(PerformanceCycle $cycle): PerformanceCycle
    {
        if ($cycle->status !== 'draft') throw new RuntimeException('Only a draft performance cycle can be opened.');
        $cycle->update(['status'=>'open']); return $cycle->refresh();
    }

    public function generate(PerformanceCycle $cycle): int
    {
        if ($cycle->status !== 'open') throw new RuntimeException('Performance cycle must be open.');
        $count=0; Employee::whereIn('status',['active','on_leave'])->orderBy('id')->each(function(Employee $employee)use($cycle,&$count){PerformanceReview::firstOrCreate(['performance_cycle_id'=>$cycle->id,'employee_id'=>$employee->id],['status'=>'draft']);$count++;}); return $count;
    }

    public function saveDraft(PerformanceReview $review, array $data, ?int $reviewerId): PerformanceReview
    {
        if ($review->status !== 'draft' || $review->cycle->status !== 'open') throw new RuntimeException('Only draft reviews in an open cycle can be edited.');
        $review->update($this->validatedPayload($data)+['reviewer_id'=>$reviewerId]); return $review->refresh();
    }

    public function submit(PerformanceReview $review, array $data, ?int $reviewerId): PerformanceReview
    {
        return DB::transaction(function()use($review,$data,$reviewerId){$review=PerformanceReview::with('cycle')->whereKey($review->id)->lockForUpdate()->firstOrFail();if($review->status!=='draft'||$review->cycle->status!=='open')throw new RuntimeException('Only draft reviews in an open cycle can be submitted.');$payload=$this->validatedPayload($data);foreach(['overall_rating','goals_rating','competency_rating'] as $field)if($payload[$field]===null)throw new RuntimeException('All three ratings are required to submit a review.');$review->update($payload+['reviewer_id'=>$reviewerId,'status'=>'submitted','submitted_at'=>now()]);return $review->refresh();});
    }

    public function acknowledge(PerformanceReview $review, Employee $employee, ?string $comment): PerformanceReview
    {
        return DB::transaction(function()use($review,$employee,$comment){$review=PerformanceReview::whereKey($review->id)->lockForUpdate()->firstOrFail();if($review->employee_id!==$employee->id)throw new RuntimeException('This review does not belong to the employee.');if($review->status!=='submitted')throw new RuntimeException('Only submitted reviews can be acknowledged.');$review->update(['status'=>'acknowledged','employee_comment'=>$comment,'acknowledged_at'=>now()]);return $review->refresh();});
    }

    public function close(PerformanceCycle $cycle): PerformanceCycle
    {
        if ($cycle->status !== 'open') throw new RuntimeException('Only an open cycle can be closed.');
        if ($cycle->reviews()->where('status','draft')->exists()) throw new RuntimeException('Submit or resolve all draft reviews before closing the cycle.');
        $cycle->update(['status'=>'closed']); return $cycle->refresh();
    }

    private function validatedPayload(array $data): array
    {
        $payload=[]; foreach(['overall_rating','goals_rating','competency_rating'] as $field){$value=$data[$field]??null;if($value===''||$value===null){$payload[$field]=null;continue;}if(!is_numeric($value)||(float)$value<1||(float)$value>5)throw new RuntimeException('Ratings must be between 1.00 and 5.00.');$payload[$field]=number_format((float)$value,2,'.','');}
        foreach(['strengths','improvements','goals'] as $field){$value=trim((string)($data[$field]??''));$payload[$field]=$value!==''?$value:null;} return $payload;
    }
}
