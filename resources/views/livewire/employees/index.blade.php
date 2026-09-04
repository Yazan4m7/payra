<div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">{{ $editingId ? __('hr.edit_employee') : __('hr.add_employee') }}</h2>
                @if($editingId)
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelEdit">{{ __('hr.cancel') }}</button>
                @endif
            </div>

            <form wire:submit="save" class="row g-2">
                <div class="col-md-4"><input class="form-control" wire:model="form.name" placeholder="{{__('hr.name')}}"></div>
                <div class="col-md-4"><input class="form-control" wire:model="form.national_id" placeholder="{{__('hr.national_id')}}"></div>
                <div class="col-md-4"><input class="form-control" wire:model="form.job_title" placeholder="{{__('hr.job_title')}}"></div>

                <div class="col-md-3"><label class="form-label small">{{ __('hr.hire_date') }}</label><input class="form-control" wire:model="form.hire_date" type="date"></div>
                <div class="col-md-3"><label class="form-label small">{{ __('hr.ssc_number') }}</label><input class="form-control" wire:model="form.ssc_number" placeholder="{{__('hr.ssc_number')}}"></div>
                <div class="col-md-3"><label class="form-label small">{{ __('hr.ssc_enrollment_date') }}</label><input class="form-control" wire:model="form.ssc_enrollment_date" type="date"></div>
                <div class="col-md-3"><label class="form-label small">{{ __('hr.salary_jod') }}</label><input class="form-control" wire:model="form.salary" inputmode="decimal" placeholder="0.000"></div>

                <div class="col-md-4"><input class="form-control" wire:model="form.bank_iban" placeholder="IBAN"></div>
                <div class="col-md-3"><select class="form-select" wire:model="form.status"><option value="active">{{ __('hr.active') }}</option><option value="on_leave">{{ __('hr.on_leave') }}</option><option value="inactive">{{ __('hr.inactive') }}</option><option value="terminated">{{ __('hr.terminated') }}</option></select></div>
                <div class="col-md-5"></div>

                <div class="col-md-5"><input class="form-control" wire:model="form.email" type="email" placeholder="{{__('hr.employee_portal_email')}}"></div>
                <div class="col-md-4"><input class="form-control" type="password" wire:model="form.password" placeholder="{{ $editingId ? __('hr.new_password_optional') : __('hr.initial_password') }}"></div>
                <div class="col-md-3"><button class="btn btn-primary w-100">{{ $editingId ? __('hr.update') : __('hr.save') }}</button></div>
            </form>

            @if($errors->any())
                <div class="alert alert-danger py-2 small mt-3 mb-0">{{ $errors->first() }}</div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-2 align-items-center mb-3">
                <div class="col-lg-6"><input class="form-control" wire:model.live.debounce.300ms="search" placeholder="{{__('hr.search')}}"></div>
                <div class="col-lg-6 text-lg-end small text-muted">{{ __('hr.employee_data_is_tenant_isolated') }}</div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>{{__('hr.name')}}</th><th>{{__('hr.job_title')}}</th><th>{{__('hr.salary_jod')}}</th><th>{{__('hr.ssc_number')}}</th><th>{{__('hr.portal')}}</th><th>{{__('hr.status')}}</th><th></th></tr></thead>
                    <tbody>
                    @forelse($rows as $e)
                        @php($sscTask = $e->onboardingTasks->first())
                        <tr>
                            <td><strong>{{$e->name}}</strong><div class="small text-muted">{{$e->national_id}}</div></td>
                            <td>{{$e->job_title ?: '—'}}</td>
                            <td class="text-nowrap">{{$e->salary}} JOD</td>
                            <td>
                                @if($e->isSscRegistered())
                                    <span class="text-success">{{$e->ssc_number}}</span><div class="small text-muted">{{$e->ssc_enrollment_date->toDateString()}}</div>
                                @else
                                    <span class="badge text-bg-warning">{{ $e->ssc_number ? __('hr.ssc_date_missing') : __('hr.not_registered') }}</span>
                                    @if($sscTask)<div class="small text-danger mt-1">{{ __('hr.due') }}: {{$sscTask->due_date->toDateString()}}</div>@endif
                                @endif
                            </td>
                            <td>{{$e->user?->email ?: '—'}} @if($e->user && !$e->user->active)<span class="badge text-bg-secondary">{{ __('hr.disabled') }}</span>@endif</td>
                            <td><span class="badge text-bg-light">{{ __('hr.status_'.$e->status) }}</span></td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary" wire:click="startEdit({{$e->id}})">{{ __('hr.edit') }}</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('hr.no_records') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{$rows->links()}}
        </div>
    </div>
</div>
