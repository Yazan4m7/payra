<div>
    @error('termination')<div class="alert alert-danger">{{ $message }}</div>@enderror
    <div class="card mb-3"><div class="card-body">
        <h2 class="h5">{{ __('hr.calculate_and_terminate') }}</h2>
        <div class="row g-2">
            <div class="col-md-4"><select class="form-select" wire:model="employee_id"><option value="">{{__('hr.employee')}}</option>@foreach($employees as $e)<option value="{{$e->id}}">{{$e->name}}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small">{{ __('hr.date') }}</label><input class="form-control" type="date" wire:model="termination_date"></div>
            <div class="col-md-2"><label class="form-label small">{{ __('hr.notice_given') }}</label><input class="form-control" type="date" wire:model="notice_given_at"></div>
            <div class="col-md-4"><label class="form-label small">{{ __('hr.reason') }}</label><input class="form-control" wire:model="reason" placeholder="{{__('hr.reason')}}"></div>
            <div class="col-12"><button wire:click="save" wire:loading.attr="disabled" class="btn btn-danger">{{__('hr.calculate_and_terminate')}}</button></div>
        </div>
    </div></div>

    <div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>{{__('hr.employee')}}</th><th>{{__('hr.date')}}</th><th>{{__('hr.prorated_salary')}}</th><th>{{__('hr.unused_leave')}}</th><th>{{__('hr.notice_pay')}}</th><th>{{__('hr.final_settlement')}}</th></tr></thead>
        <tbody>@forelse($records as $r)<tr><td><strong>{{$r->employee->name}}</strong><div class="small text-muted">{{$r->reason}}</div></td><td>{{$r->termination_date->toDateString()}}</td><td class="text-jod">{{$r->prorated_salary}}</td><td>{{$r->unused_leave_days}} {{ __('hr.days') }}<div class="small text-muted text-jod">{{$r->unused_leave_pay}} JOD</div></td><td class="text-jod">{{$r->notice_pay_in_lieu}}</td><td class="text-jod"><strong>{{$r->final_settlement}}</strong></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">{{ __('hr.no_records') }}</td></tr>@endforelse</tbody>
    </table></div></div>
</div>
