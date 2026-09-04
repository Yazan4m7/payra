<?php
namespace App\Livewire\Users;
use App\Models\User; use Livewire\Component;
class Index extends Component
{
    public string $name=''; public string $email=''; public string $password=''; public string $role='hr';
    public function save(){ $this->authorize('company-admin');$d=$this->validate(['name'=>'required|string|max:255','email'=>'required|email|unique:users,email','password'=>'required|string|min:8','role'=>'required|in:company_admin,hr']);User::create(array_merge($d,['locale'=>'ar','active'=>true]));$this->reset('name','email','password');session()->flash('success',__('hr.saved')); }
    public function toggle(int $id){$this->authorize('company-admin');$u=User::findOrFail($id);abort_if($u->id===auth()->id(),422,'You cannot disable your own account.');$u->update(['active'=>!$u->active]);}
    public function render(){return view('livewire.users.index',['users'=>User::whereIn('role',['company_admin','hr'])->orderBy('name')->get()]);}
}
