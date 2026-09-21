<?php

namespace App\Livewire\Organization;

use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Department;
use Livewire\Component;

class Structure extends Component
{
    public string $branchCode=''; public string $branchName=''; public string $branchCity='';
    public string $departmentCode=''; public string $departmentName=''; public ?int $departmentBranchId=null;
    public string $costCenterCode=''; public string $costCenterName=''; public ?int $costCenterDepartmentId=null;

    public function createBranch(): void
    {
        $this->authorize('manage-hr');
        $data=$this->validate(['branchCode'=>'required|string|max:50','branchName'=>'required|string|max:255','branchCity'=>'nullable|string|max:255']);
        Branch::create(['code'=>trim($data['branchCode']),'name'=>trim($data['branchName']),'city'=>trim($data['branchCity']?:'')?:null]);
        $this->reset('branchCode','branchName','branchCity');
    }
    public function createDepartment(): void
    {
        $this->authorize('manage-hr');
        $data=$this->validate(['departmentCode'=>'required|string|max:50','departmentName'=>'required|string|max:255','departmentBranchId'=>'nullable|exists:branches,id']);
        Department::create(['code'=>trim($data['departmentCode']),'name'=>trim($data['departmentName']),'branch_id'=>$data['departmentBranchId']]);
        $this->reset('departmentCode','departmentName','departmentBranchId');
    }
    public function createCostCenter(): void
    {
        $this->authorize('manage-hr');
        $data=$this->validate(['costCenterCode'=>'required|string|max:50','costCenterName'=>'required|string|max:255','costCenterDepartmentId'=>'nullable|exists:departments,id']);
        CostCenter::create(['code'=>trim($data['costCenterCode']),'name'=>trim($data['costCenterName']),'department_id'=>$data['costCenterDepartmentId']]);
        $this->reset('costCenterCode','costCenterName','costCenterDepartmentId');
    }
    public function render(){return view('livewire.organization.structure',['branches'=>Branch::orderBy('code')->get(),'departments'=>Department::with('branch')->orderBy('code')->get(),'costCenters'=>CostCenter::with('department')->orderBy('code')->get()]);}
}
