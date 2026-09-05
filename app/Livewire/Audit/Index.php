<?php
namespace App\Livewire\Audit;
use App\Models\AuditLog; use Livewire\Component; use Livewire\WithPagination;
class Index extends Component { use WithPagination; public string $event=''; public string $search=''; public function render(){ $q=AuditLog::with('user')->latest('id');if($this->event!=='')$q->where('event',$this->event);if($this->search!=='')$q->where(function($x){$x->where('auditable_type','like','%'.$this->search.'%')->orWhere('auditable_id',$this->search);});return view('livewire.audit.index',['logs'=>$q->paginate(50)]);} }
