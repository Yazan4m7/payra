<div>
    @error('leave')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
    <div class="card mb-3"><div class="card-body">
        <h2 class="h5">{{ __('hr.request_leave') }}</h2>
        <form wire:submit="create" class="row g-2">
            <div class="col-md-3"><select class="form-select" wire:model="employee_id"><option value="">{{ __('hr.employee') }}</option>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" wire:model="type"><option value="annual">{{ __('hr.annual') }}</option><option value="sick">{{ __('hr.sick') }}</option></select></div>
            <div class="col-md-2"><input class="form-control" type="date" wire:model="start_date"></div>
            <div class="col-md-2"><input class="form-control" type="date" wire:model="end_date"></div>
            <div class="col-md-3"><input class="form-control" wire:model="reason" placeholder="{{ __('hr.reason') }}"></div>
            <div class="col-md-9"><label class="form-check mt-2"><input class="form-check-input" type="checkbox" wire:model="hospitalized"><span class="form-check-label">{{ __('hr.hospitalized') }}</span></label></div>
            <div class="col-md-3"><button class="btn btn-primary w-100">{{ __('hr.submit') }}</button></div>
        </form>
        @error('employee_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        @error('end_date')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
    </div></div>

    <div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>{{__('hr.employee')}}</th><th>{{__('hr.type')}}</th><th>{{__('hr.dates')}}</th><th>{{__('hr.status')}}</th><th>{{ __('hr.balance') }}</th><th>{{ __('hr.reason') }}</th><th></th></tr></thead>
        <tbody>@forelse($requests as $r)<tr><td>{{$r->employee->name}}</td><td>{{ __('hr.'.$r->type) }}</td><td>{{$r->start_date->toDateString()}} → {{$r->end_date->toDateString()}} @if($r->status==='approved')<div class="small text-muted">{{$r->days}} {{ __('hr.days') }}</div>@endif</td><td><span class="badge text-bg-light">{{ __('hr.status_'.$r->status) }}</span></td><td>@php($b=$r->employee->leaveBalances->firstWhere('year',$r->start_date->year))<div class="small">{{ __('hr.annual') }}: {{$b?->annual_remaining ?? '—'}}<br>{{ __('hr.sick') }}: {{$b?->sick_remaining ?? '—'}}</div></td><td>{{ $r->reason ?: '—' }}</td><td class="text-nowrap">@if($r->status==='pending')<button wire:click="approve({{$r->id}})" class="btn btn-sm btn-success">{{__('hr.approve')}}</button> <button wire:click="reject({{$r->id}})" class="btn btn-sm btn-outline-danger">{{__('hr.reject')}}</button>@endif</td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">{{ __('hr.no_records') }}</td></tr>@endforelse</tbody>
    </table></div></div>
</div>
