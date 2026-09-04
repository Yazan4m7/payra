<div>
    @error('overtime')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
    <div class="card mb-3"><div class="card-body">
        <h2 class="h5">{{ __('hr.overtime') }}</h2>
        <form wire:submit="create" class="row g-2">
            <div class="col-md-4"><select class="form-select" wire:model="employee_id"><option value="">{{ __('hr.employee') }}</option>@foreach($employees as $e)<option value="{{$e->id}}">{{$e->name}}</option>@endforeach</select></div>
            <div class="col-md-3"><input class="form-control" type="date" wire:model="date"></div>
            <div class="col-md-2"><input class="form-control" inputmode="decimal" wire:model="hours" placeholder="{{ __('hr.hours') }}"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100">{{ __('hr.submit') }}</button></div>
            <div class="col-12"><textarea class="form-control" rows="2" wire:model="notes" placeholder="{{ __('hr.reason') }}"></textarea></div>
        </form>
    </div></div>

    <div class="row g-3 mb-3">
        @foreach($employees as $employee)
            @php($cap = $capStatuses[$employee->id] ?? null)
            @if($cap && ($cap['warning'] || $cap['hours'] !== '0'))
                <div class="col-md-4"><div class="card h-100"><div class="card-body py-2"><strong>{{ $employee->name }}</strong><div class="small {{ $cap['warning'] ? 'text-danger' : 'text-muted' }}">{{ __('hr.hours_this_month') }}: {{ $cap['hours'] }} @if($cap['cap']) / {{ __('hr.cap') }} {{ $cap['cap'] }} @endif</div></div></div></div>
            @endif
        @endforeach
    </div>

    <div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>{{__('hr.employee')}}</th><th>{{__('hr.date')}}</th><th>{{__('hr.hours')}}</th><th>{{__('hr.rate_type')}}</th><th>{{__('hr.status')}}</th><th>{{ __('hr.reason') }}</th><th></th></tr></thead>
        <tbody>@forelse($entries as $r)<tr><td>{{$r->employee->name}}</td><td>{{$r->date->toDateString()}}</td><td>{{$r->hours}}</td><td>{{ $r->rate_type === 'rest_holiday' ? __('hr.rate_rest_holiday') : __('hr.rate_standard') }}</td><td>{{ __('hr.status_'.$r->status) }}</td><td>{{ $r->notes ?: '—' }}</td><td class="text-nowrap">@if($r->status==='pending')<button wire:click="approve({{$r->id}})" class="btn btn-sm btn-success">{{__('hr.approve')}}</button> <button wire:click="reject({{$r->id}})" class="btn btn-sm btn-outline-danger">{{__('hr.reject')}}</button>@endif</td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">{{ __('hr.no_records') }}</td></tr>@endforelse</tbody>
    </table></div></div>
</div>
