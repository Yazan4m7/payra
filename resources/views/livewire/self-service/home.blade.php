<div>
    @error('leave')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @error('overtime')<div class="alert alert-danger">{{ $message }}</div>@enderror
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted">{{__('hr.annual_leave_remaining')}}</div><div class="fs-2">{{$balance?->annual_remaining ?? '—'}}</div></div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted">{{__('hr.sick_leave_remaining')}}</div><div class="fs-2">{{$balance?->sick_remaining ?? '—'}}</div></div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted">{{__('hr.salary_jod')}}</div><div class="fs-2 text-jod">{{$employee->salary}}</div></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card mb-3"><div class="card-body">
                <h2 class="h5">{{__('hr.request_leave')}}</h2>
                <select class="form-select mb-2" wire:model="type"><option value="annual">{{ __('hr.annual') }}</option><option value="sick">{{ __('hr.sick') }}</option></select>
                <div class="row g-2"><div class="col"><input class="form-control" type="date" wire:model="start_date"></div><div class="col"><input class="form-control" type="date" wire:model="end_date"></div></div>
                <label class="form-check mt-2"><input class="form-check-input" type="checkbox" wire:model="hospitalized"><span class="form-check-label">{{__('hr.hospitalized')}}</span></label>
                <textarea class="form-control my-2" wire:model="reason" placeholder="{{__('hr.reason')}}"></textarea>
                <button class="btn btn-primary" wire:click="requestLeave">{{__('hr.submit')}}</button>
                @error('end_date')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div></div>

            <div class="card"><div class="card-body">
                <h2 class="h5">{{__('hr.overtime')}}</h2>
                <div class="small mb-3 {{ $capStatus['warning'] ? 'text-danger' : 'text-muted' }}">{{ __('hr.hours_this_month') }}: {{ $capStatus['hours'] }} @if($capStatus['cap']) / {{ __('hr.cap') }} {{ $capStatus['cap'] }} @endif</div>
                <div class="row g-2"><div class="col-md-7"><input class="form-control" type="date" wire:model="overtime_date"></div><div class="col-md-5"><input class="form-control" wire:model="overtime_hours" inputmode="decimal" placeholder="{{__('hr.hours')}}"></div><div class="col-12"><textarea class="form-control" wire:model="overtime_notes" placeholder="{{__('hr.reason')}}"></textarea></div><div class="col-12"><button class="btn btn-primary" wire:click="requestOvertime">{{__('hr.submit')}}</button></div></div>
            </div></div>
        </div>

        <div class="col-lg-7">
            <div class="card mb-3"><div class="card-body">
                <h2 class="h5">{{__('hr.payslips')}}</h2>
                @forelse($payslips as $p)
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between"><strong>{{$p->payrollRun->month}}/{{$p->payrollRun->year}}</strong><a href="{{route('payslips.pdf',$p)}}">PDF</a></div>
                        <div class="small text-muted">{{ __('hr.gross') }} {{$p->gross_salary}} · OT +{{$p->overtime_pay}} · {{ __('hr.ssc_employee') }} -{{$p->ssc_employee}} · {{ __('hr.income_tax') }} -{{$p->income_tax}} · {{ __('hr.surcharge') }} -{{$p->surcharge}}</div>
                        <div><strong>{{ __('hr.net') }}: {{$p->net_salary}} JOD</strong></div>
                    </div>
                @empty<p class="text-muted">—</p>@endforelse
            </div></div>

            <div class="row g-3">
                <div class="col-md-6"><div class="card h-100"><div class="card-body"><h2 class="h6">{{ __('hr.recent_leave_requests') }}</h2>@forelse($requests as $r)<div class="border-bottom py-2 small"><strong>{{ __('hr.'.$r->type) }}</strong> · {{$r->start_date->toDateString()}} → {{$r->end_date->toDateString()}}<div class="text-muted">{{ __('hr.status_'.$r->status) }}</div></div>@empty<div class="text-muted">—</div>@endforelse</div></div></div>
                <div class="col-md-6"><div class="card h-100"><div class="card-body"><h2 class="h6">{{ __('hr.recent_overtime') }}</h2>@forelse($overtimeEntries as $r)<div class="border-bottom py-2 small"><strong>{{$r->date->toDateString()}}</strong> · {{$r->hours}} {{ __('hr.hours') }}<div class="text-muted">{{ $r->rate_type === 'rest_holiday' ? __('hr.rate_rest_holiday') : __('hr.rate_standard') }} · {{ __('hr.status_'.$r->status) }}</div></div>@empty<div class="text-muted">—</div>@endforelse</div></div></div>
            </div>
        </div>
    </div>
</div>
