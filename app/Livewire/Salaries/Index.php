<?php
namespace App\Livewire\Salaries;
use App\Models\Employee; use App\Models\EmployeeSalaryHistory; use App\Services\SalaryHistoryService; use Carbon\Carbon; use Livewire\Component;
class Index extends Component
{
public array $form=[]; public function mount():void{$this->form=['employee_id'=>'','amount'=>'','effective_from'=>now()->toDateString(),'reason'=>''];}
public function save(SalaryHistoryService $service):void{$this->authorize('manage-hr');$d=$this->validate(['form.employee_id'=>'required|exists:employees,id','form.amount'=>'required|decimal:0,3|min:0','form.effective_from'=>'required|date','form.reason'=>'nullable|string|max:255'])['form'];try{$service->record(Employee::findOrFail($d['employee_id']),$d['amount'],Carbon::parse($d['effective_from']),$d['reason']?:null,auth()->id());$this->form['amount']='';$this->form['reason']='';session()->flash('success',__('hr.saved'));}catch(\RuntimeException $e){$this->addError('salary',$e->getMessage());}}
public function render(){return view('livewire.salaries.index',['employees'=>Employee::orderBy('name')->get(['id','name']),'rows'=>EmployeeSalaryHistory::with('employee')->orderByDesc('effective_from')->orderByDesc('id')->get()]);}
}
