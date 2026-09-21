<?php

namespace App\Livewire\Performance;

use App\Models\PerformanceCycle;
use App\Models\PerformanceReview;
use App\Services\PerformanceReviewService;
use Livewire\Component;

class Index extends Component
{
    public array $cycle=['name'=>'','starts_on'=>'','ends_on'=>''];
    public ?int $editingReviewId=null;
    public array $review=['overall_rating'=>'','goals_rating'=>'','competency_rating'=>'','strengths'=>'','improvements'=>'','goals'=>''];

    public function createCycle():void{$this->authorize('performance.manage');$data=$this->validate(['cycle.name'=>'required|string|max:255','cycle.starts_on'=>'required|date','cycle.ends_on'=>'required|date|after_or_equal:cycle.starts_on'])['cycle'];PerformanceCycle::create($data+['status'=>'draft','created_by'=>auth()->id()]);$this->cycle=['name'=>'','starts_on'=>'','ends_on'=>''];}
    public function openCycle(int $id,PerformanceReviewService $service):void{$this->authorize('performance.manage');try{$service->open(PerformanceCycle::findOrFail($id));}catch(\RuntimeException $e){$this->addError('performance',$e->getMessage());}}
    public function generate(int $id,PerformanceReviewService $service):void{$this->authorize('performance.manage');try{$service->generate(PerformanceCycle::findOrFail($id));}catch(\RuntimeException $e){$this->addError('performance',$e->getMessage());}}
    public function closeCycle(int $id,PerformanceReviewService $service):void{$this->authorize('performance.manage');try{$service->close(PerformanceCycle::findOrFail($id));}catch(\RuntimeException $e){$this->addError('performance',$e->getMessage());}}
    public function editReview(int $id):void{$this->authorize('performance.manage');$r=PerformanceReview::findOrFail($id);$this->editingReviewId=$r->id;foreach(array_keys($this->review) as $f)$this->review[$f]=$r->{$f}??'';}
    public function saveReview(PerformanceReviewService $service):void{$this->authorize('performance.manage');try{$service->saveDraft(PerformanceReview::findOrFail($this->editingReviewId),$this->review,auth()->id());$this->editingReviewId=null;}catch(\RuntimeException $e){$this->addError('performance',$e->getMessage());}}
    public function submitReview(PerformanceReviewService $service):void{$this->authorize('performance.manage');try{$service->submit(PerformanceReview::findOrFail($this->editingReviewId),$this->review,auth()->id());$this->editingReviewId=null;}catch(\RuntimeException $e){$this->addError('performance',$e->getMessage());}}
    public function render(){return view('livewire.performance.index',['cycles'=>PerformanceCycle::withCount('reviews')->latest()->get(),'reviews'=>PerformanceReview::with(['cycle','employee'])->latest('id')->limit(200)->get()]);}
}
