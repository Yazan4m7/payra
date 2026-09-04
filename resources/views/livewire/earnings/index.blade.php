<div>
    <div class="card mb-4">
        <div class="card-header fw-semibold">{{ $editingId ? __('payroll.edit_earning') : __('payroll.add_earning') }}</div>
        <div class="card-body">
            <form wire:submit="save" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('hr.employee') }}</label>
                    <select class="form-select" wire:model="form.employee_id">
                        <option value="">—</option>
                        @foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach
                    </select>
                    @error('form.employee_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('payroll.category') }}</label>
                    <select class="form-select" wire:model="form.category">
                        <option value="allowance">{{ __('payroll.allowance') }}</option>
                        <option value="bonus">{{ __('payroll.bonus') }}</option>
                        <option value="commission">{{ __('payroll.commission') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('hr.name') }}</label>
                    <input class="form-control" wire:model="form.name">
                    @error('form.name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('payroll.amount_jod') }}</label>
                    <input class="form-control" inputmode="decimal" wire:model="form.amount">
                    @error('form.amount')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">{{ __('payroll.recurrence') }}</label>
                    <select class="form-select" wire:model.live="form.recurrence">
                        <option value="recurring">{{ __('payroll.recurring') }}</option>
                        <option value="one_time">{{ __('payroll.one_time') }}</option>
                    </select>
                </div>
                @if($form['recurrence'] === 'recurring')
                    <div class="col-md-3"><label class="form-label">{{ __('payroll.starts_on') }}</label><input type="date" class="form-control" wire:model="form.starts_on"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('payroll.ends_on') }}</label><input type="date" class="form-control" wire:model="form.ends_on"></div>
                @else
                    <div class="col-md-3"><label class="form-label">{{ __('payroll.one_time_date') }}</label><input type="date" class="form-control" wire:model="form.one_time_date"></div>
                @endif
                <div class="col-md-3 d-flex align-items-end gap-3">
                    <div class="form-check"><input class="form-check-input" type="checkbox" wire:model="form.taxable" id="earningTaxable"><label class="form-check-label" for="earningTaxable">{{ __('payroll.taxable') }}</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" wire:model="form.ssc_applicable" id="earningSsc"><label class="form-check-label" for="earningSsc">{{ __('payroll.ssc_applicable') }}</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" wire:model="form.active" id="earningActive"><label class="form-check-label" for="earningActive">{{ __('hr.active') }}</label></div>
                </div>
                <div class="col-12"><label class="form-label">{{ __('payroll.notes') }}</label><textarea class="form-control" rows="2" wire:model="form.notes"></textarea></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary">{{ __('hr.save') }}</button>@if($editingId)<button type="button" wire:click="cancelEdit" class="btn btn-outline-secondary">{{ __('hr.cancel') }}</button>@endif</div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>{{ __('hr.employee') }}</th><th>{{ __('payroll.category') }}</th><th>{{ __('hr.name') }}</th><th>{{ __('payroll.amount_jod') }}</th><th>{{ __('payroll.recurrence') }}</th><th>{{ __('payroll.flags') }}</th><th></th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->employee->name }}</td>
                        <td>{{ __('payroll.'.$row->category) }}</td>
                        <td>{{ $row->name }}</td>
                        <td class="text-jod">{{ $row->amount }}</td>
                        <td>{{ $row->recurring ? __('payroll.recurring') : __('payroll.one_time') }}</td>
                        <td class="small">{{ $row->taxable ? __('payroll.taxable') : __('payroll.non_taxable') }} · {{ $row->ssc_applicable ? __('payroll.ssc_applicable') : __('payroll.ssc_exempt') }}</td>
                        <td class="text-nowrap"><button class="btn btn-sm btn-outline-primary" wire:click="startEdit({{ $row->id }})">{{ __('hr.edit') }}</button> <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $row->id }})" wire:confirm="{{ __('payroll.confirm_delete') }}">{{ __('hr.delete') }}</button></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('hr.no_records') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
