<div>
    <div class="card mb-4"><div class="card-body">
        <form wire:submit="create" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">{{__('hr.month')}}</label><input class="form-control" type="number" min="1" max="12" wire:model="month"></div>
            <div class="col-md-3"><label class="form-label">{{__('hr.year')}}</label><input class="form-control" type="number" wire:model="year"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100" wire:loading.attr="disabled">{{__('hr.create_payroll')}}</button></div>
        </form>
        @error('payroll')<div class="alert alert-danger py-2 mt-3 mb-0">{{ $message }}</div>@enderror
    </div></div>

    <div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>{{__('hr.period')}}</th><th>{{__('hr.status')}}</th><th>{{ __('hr.settings_version') }}</th><th>{{__('hr.payslips')}}</th><th></th></tr></thead>
        <tbody>
        @forelse($runs as $run)
            <tr>
                <td>{{$run->month}}/{{$run->year}}</td>
                <td><span class="badge text-bg-light">{{ __('hr.status_'.$run->status) }}</span></td>
                <td>{{$run->complianceSetting?->version_label ?: '—'}}</td>
                <td>{{$run->payslips_count}}</td>
                <td class="text-nowrap">@if($run->status==='draft')<button wire:click="process({{$run->id}})" wire:loading.attr="disabled" class="btn btn-sm btn-success">{{__('hr.run_payroll')}}</button>@elseif($run->status==='completed')<a class="btn btn-sm btn-outline-primary" href="{{route('payroll.bank-export',$run)}}">{{__('hr.bank_export')}}</a>@endif</td>
            </tr>
            @if($run->status==='completed')
                <tr><td colspan="5" class="bg-body-tertiary"><div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>{{__('hr.employee')}}</th><th>{{__('payroll.base_salary')}}</th><th>OT</th><th>{{__('payroll.earnings_total')}}</th><th>{{__('payroll.deductions_total')}}</th><th>{{__('hr.ssc_employee')}}</th><th>{{__('hr.income_tax')}}</th><th>{{__('hr.surcharge')}}</th><th>{{__('hr.net')}}</th><th></th></tr></thead>
                    <tbody>@foreach($run->payslips as $p)<tr><td>{{$p->employee->name}}</td><td class="text-jod">{{$p->gross_salary}}</td><td class="text-jod">{{$p->overtime_pay}}</td><td class="text-jod">{{$p->earnings_total}}</td><td class="text-jod">{{$p->deductions_total}}</td><td class="text-jod">{{$p->ssc_employee}}</td><td class="text-jod">{{$p->income_tax}}</td><td class="text-jod">{{$p->surcharge}}</td><td class="text-jod"><strong>{{$p->net_salary}}</strong></td><td><a href="{{route('payslips.pdf',$p)}}">PDF</a></td></tr>@endforeach</tbody>
                </table></div></td></tr>
            @endif
        @empty<tr><td colspan="5" class="text-center text-muted py-4">{{ __('hr.no_records') }}</td></tr>@endforelse
        </tbody>
    </table></div></div>
</div>
