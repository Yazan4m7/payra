<div>
    <div class="row g-3 mb-4">
        <x-kpi :label="__('hr.employees')" :value="$employees"/>
        <x-kpi :label="__('hr.pending_leave')" :value="$pendingLeave"/>
        <x-kpi :label="__('hr.pending_overtime')" :value="$pendingOvertime"/>
        <x-kpi :label="__('hr.latest_payroll')" :value="$latestPayroll ? $latestPayroll->month.'/'.$latestPayroll->year : '—'"/>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">{{__('hr.compliance_alerts')}}</h2><span class="badge text-bg-light">{{ __('hr.settings_version') }}: {{ $compliance['settings_version'] ?: '—' }}</span></div>
                @if($compliance['settings_stale'])<div class="alert alert-warning">{{__('hr.settings_stale')}}</div>@endif
                @if($compliance['holidays_missing'])<div class="alert alert-warning">{{__('hr.holidays_missing')}}</div>@endif

                <div class="row g-3">
                    <div class="col-md-6"><div class="border rounded p-3 h-100"><div class="d-flex justify-content-between"><strong>{{__('hr.pending_ssc')}}</strong><span class="badge text-bg-warning">{{count($compliance['pending_ssc'])}}</span></div><div class="compliance-list mt-2">@forelse($compliance['pending_ssc'] as $employee)<div class="small border-top py-2"><strong>{{ $employee->name }}</strong><div class="text-muted">{{ __('hr.hire_date') }}: {{ $employee->hire_date->toDateString() }}</div></div>@empty<div class="small text-muted">{{ __('hr.no_records') }}</div>@endforelse</div></div></div>
                    <div class="col-md-6"><div class="border rounded p-3 h-100"><div class="d-flex justify-content-between"><strong>{{__('hr.near_overtime_cap')}}</strong><span class="badge text-bg-danger">{{count($compliance['near_overtime_caps'])}}</span></div><div class="compliance-list mt-2">@forelse($compliance['near_overtime_caps'] as $row)<div class="small border-top py-2"><strong>{{ $row->employee?->name }}</strong><div class="text-muted">{{ $row->total_hours }} {{ __('hr.hours') }} · {{ number_format((float)$row->cap_percent,1) }}%</div></div>@empty<div class="small text-muted">{{ __('hr.no_records') }}</div>@endforelse</div></div></div>
                    <div class="col-12"><div class="border rounded p-3"><div class="d-flex justify-content-between"><strong>{{ __('hr.overdue_onboarding') }}</strong><span class="badge text-bg-danger">{{ count($compliance['overdue_onboarding']) }}</span></div>@forelse($compliance['overdue_onboarding'] as $task)<div class="small border-top py-2 mt-2"><strong>{{ app()->getLocale()==='ar' ? $task->title_ar : $task->title_en }}</strong> — {{ $task->employee?->name }} <span class="text-danger">({{ __('hr.due') }} {{ $task->due_date->toDateString() }})</span></div>@empty<div class="small text-muted mt-2">{{ __('hr.no_records') }}</div>@endforelse</div></div>
                </div>
            </div></div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100"><div class="card-body"><h2 class="h5">{{__('hr.filing_deadlines')}}</h2>@forelse($compliance['filing_deadlines'] as $deadline)<div class="border-bottom py-3"><strong>{{ $deadline['name'] ?? '—' }}</strong><div class="small text-muted">{{ $deadline['rule'] ?? (($deadline['due_day'] ?? null) ? 'Day '.$deadline['due_day'] : '—') }}</div></div>@empty<p class="text-muted">{{__('hr.none_configured')}}</p>@endforelse</div></div>
        </div>
    </div>
</div>
