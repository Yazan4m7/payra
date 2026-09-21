<?php
namespace App\Livewire\Absences;
use App\Models\Employee; use App\Models\UnpaidAbsence; use App\Services\AbsenceService; use App\Services\ComplianceSettingsService; use Carbon\Carbon; use Livewire\Component;
class Index extends Component
{
public array $form=[]; public function mount():void{$this->form=['employee_id'=>'','type'=>'unpaid_leave','start_date'=>now()->toDateString(),'end_date'=>now()->toDateString(),'reason'=>''];}
public function create(AbsenceService $service):void{$this->authorize('manage-hr');$d=$this->validate(['form.employee_id'=>'required|exists:employees,id','form.type'=>'required|in:unpaid_leave,absence','form.start_date'=>'required|date','form.end_date'=>'required|date|after_or_equal:form.start_date','form.reason'=>'nullable|string|max:2000'])['form'];try{$service->create(Employee::findOrFail($d['employee_id']),$d['type'],Carbon::parse($d['start_date']),Carbon::parse($d['end_date']),$d['reason']?:null);session()->flash('success',__('hr.saved'));}catch(\RuntimeException $e){$this->addError('absence',$e->getMessage());}}
public function approve(int $id,AbsenceService $service,ComplianceSettingsService $compliance):void{$this->authorize('manage-hr');$a=UnpaidAbsence::findOrFail($id);try{$settings=$compliance->forDate($a->start_date)->settings;$service->approve($a,auth()->id(),$settings);session()->flash('success',__('hr.approved'));}catch(\RuntimeException $e){$this->addError('absence',$e->getMessage());}}
public function reject(int $id,AbsenceService $service):void{$this->authorize('manage-hr');try{$service->reject(UnpaidAbsence::findOrFail($id),auth()->id());session()->flash('success',__('hr.saved'));}catch(\RuntimeException $e){$this->addError('absence',$e->getMessage());}}
public function render(){return view('livewire.absences.index',['employees'=>Employee::whereIn('status',['active','on_leave'])->orderBy('name')->get(['id','name']),'rows'=>UnpaidAbsence::with('employee')->orderByDesc('start_date')->get()]);}
}
