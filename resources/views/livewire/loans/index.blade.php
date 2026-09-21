<div>
<div class="card mb-4"><div class="card-header fw-semibold">{{ $editingId ? __('payroll.edit_loan') : __('payroll.add_loan') }}</div><div class="card-body"><form wire:submit="save" class="row g-3">
<div class="col-md-4"><label class="form-label">{{ __('hr.employee') }}</label><select class="form-select" wire:model="form.employee_id"><option value="">—</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">{{ __('hr.type') }}</label><select class="form-select" wire:model="form.type"><option value="loan">{{ __('payroll.loan') }}</option><option value="advance">{{ __('payroll.advance') }}</option></select></div>
<div class="col-md-3"><label class="form-label">{{ __('hr.name') }}</label><input class="form-control" wire:model="form.name"></div>
<div class="col-md-3"><label class="form-label">{{ __('payroll.principal') }}</label><input class="form-control" inputmode="decimal" wire:model="form.principal"></div>
<div class="col-md-3"><label class="form-label">{{ __('payroll.installment') }}</label><input class="form-control" inputmode="decimal" wire:model="form.installment_amount"></div>
<div class="col-md-3"><label class="form-label">{{ __('payroll.starts_on') }}</label><input type="date" class="form-control" wire:model="form.starts_on"></div>
<div class="col-md-3"><label class="form-label">{{ __('payroll.ends_on') }}</label><input type="date" class="form-control" wire:model="form.ends_on"></div>
<div class="col-md-3"><label class="form-label">{{ __('hr.status') }}</label><select class="form-select" wire:model="form.status"><option value="active">{{ __('hr.active') }}</option><option value="paused">{{ __('payroll.paused') }}</option><option value="cancelled">{{ __('hr.status_cancelled') }}</option></select></div>
<div class="col-12"><label class="form-label">{{ __('payroll.notes') }}</label><textarea class="form-control" wire:model="form.notes"></textarea></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary">{{ __('hr.save') }}</button>@if($editingId)<button type="button" wire:click="cancelEdit" class="btn btn-outline-secondary">{{ __('hr.cancel') }}</button>@endif</div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>{{ __('hr.employee') }}</th><th>{{ __('hr.type') }}</th><th>{{ __('hr.name') }}</th><th>{{ __('payroll.principal') }}</th><th>{{ __('payroll.installment') }}</th><th>{{ __('payroll.paid') }}</th><th>{{ __('payroll.remaining') }}</th><th>{{ __('hr.status') }}</th><th></th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->employee->name }}</td><td>{{ __('payroll.'.$row->type) }}</td><td>{{ $row->name }}</td><td>{{ $row->principal }}</td><td>{{ $row->installment_amount }}</td><td>{{ $row->repayments_sum_amount ?? '0.000' }}</td><td><strong>{{ $row->remainingBalance() }}</strong></td><td>{{ $row->status }}</td><td><button class="btn btn-sm btn-outline-primary" wire:click="startEdit({{ $row->id }})">{{ __('hr.edit') }}</button></td></tr>@empty<tr><td colspan="9" class="text-center text-muted py-4">{{ __('hr.no_records') }}</td></tr>@endforelse
</tbody></table></div></div>
</div>
