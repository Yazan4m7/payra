<?php

namespace App\Livewire\Recruiting;

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\Department;
use App\Models\JobOpening;
use App\Services\RecruitingService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public array $job = ['title'=>'','department_id'=>'','location'=>'','employment_type'=>'full_time','openings_count'=>1,'description'=>''];
    public array $candidate = ['name'=>'','email'=>'','phone'=>'','national_id'=>'','source'=>'','notes'=>'','job_opening_id'=>''];
    public string $hireSalary = '';
    public string $hireDate = '';

    public function mount(): void { $this->hireDate = now()->toDateString(); }

    public function createJob(): void
    {
        $this->authorize('recruiting.manage');
        $data=$this->validate(['job.title'=>'required|string|max:255','job.department_id'=>'nullable|exists:departments,id','job.location'=>'nullable|string|max:255','job.employment_type'=>['required',Rule::in(['full_time','part_time','contract','internship'])],'job.openings_count'=>'required|integer|min:1|max:100','job.description'=>'nullable|string|max:5000'])['job'];
        JobOpening::create($data+['status'=>'open','opened_on'=>now()->toDateString(),'created_by'=>auth()->id()]);
        $this->job=['title'=>'','department_id'=>'','location'=>'','employment_type'=>'full_time','openings_count'=>1,'description'=>''];
        session()->flash('success','Job opening created.');
    }

    public function addCandidate(RecruitingService $service): void
    {
        $this->authorize('recruiting.manage');
        $data=$this->validate(['candidate.name'=>'required|string|max:255','candidate.email'=>'nullable|email|max:255','candidate.phone'=>'nullable|string|max:50','candidate.national_id'=>'nullable|string|max:50','candidate.source'=>'nullable|string|max:255','candidate.notes'=>'nullable|string|max:5000','candidate.job_opening_id'=>'required|exists:job_openings,id'])['candidate'];
        $openingId=(int)$data['job_opening_id']; unset($data['job_opening_id']);
        $candidate=Candidate::create($data);
        $service->apply($candidate,$openingId);
        $this->candidate=['name'=>'','email'=>'','phone'=>'','national_id'=>'','source'=>'','notes'=>'','job_opening_id'=>''];
        session()->flash('success','Candidate added to pipeline.');
    }

    public function transition(int $id,string $stage,RecruitingService $service): void
    {
        $this->authorize('recruiting.manage');
        try{$service->transition(CandidateApplication::findOrFail($id),$stage,auth()->id());session()->flash('success','Candidate stage updated.');}catch(\RuntimeException $e){$this->addError('recruiting',$e->getMessage());}
    }

    public function hire(int $id, RecruitingService $service): void
    {
        $this->authorize('recruiting.manage');
        $this->validate(['hireSalary'=>'required|decimal:0,3|min:0','hireDate'=>'required|date']);
        try{$service->hire(CandidateApplication::findOrFail($id),$this->hireSalary,$this->hireDate,auth()->id());session()->flash('success','Candidate hired and employee record created.');}catch(\RuntimeException $e){$this->addError('recruiting',$e->getMessage());}
    }

    public function render()
    {
        return view('livewire.recruiting.index',['departments'=>Department::where('active',true)->orderBy('name')->get(),'openings'=>JobOpening::withCount(['applications','applications as hired_count'=>fn($q)=>$q->where('stage','hired')])->latest()->get(),'applications'=>CandidateApplication::with(['candidate','opening'])->latest('id')->get()]);
    }
}
