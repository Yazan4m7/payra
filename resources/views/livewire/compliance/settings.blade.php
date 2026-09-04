<div>
    <div class="alert alert-warning"><strong>{{__('hr.compliance_settings')}}:</strong> {{__('hr.settings_warning')}}</div>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card"><div class="card-body">
                <h2 class="h5">{{__('hr.new_settings_version')}}</h2>
                <p class="small text-muted">{{ __('hr.settings_json_help') }}</p>
                <div class="row g-2 mb-2"><div class="col-md-6"><input class="form-control" wire:model="version_label" placeholder="Version label / اسم النسخة"></div><div class="col-md-6"><input class="form-control" type="date" wire:model="effective_date"></div></div>
                <textarea class="form-control font-monospace" rows="25" wire:model="json" spellcheck="false"></textarea>
                @if($errors->any())<div class="alert alert-danger py-2 small mt-2">{{ $errors->first() }}</div>@endif
                <div class="d-flex flex-wrap gap-2 mt-2"><button class="btn btn-outline-secondary" wire:click="loadTemplate" type="button">{{__('hr.load_template')}}</button><button class="btn btn-outline-secondary" wire:click="loadLatest" type="button">{{__('hr.load_latest')}}</button><button class="btn btn-primary" wire:click="save" type="button">{{__('hr.save_version')}}</button></div>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card"><div class="card-body">
                <h2 class="h5">{{__('hr.versions')}}</h2>
                @forelse($versions as $v)
                    <div class="border-bottom py-3"><div class="d-flex justify-content-between gap-2"><strong>{{$v->version_label}}</strong><span class="badge text-bg-light">{{$v->effective_date->toDateString()}}</span></div><div class="small text-muted mt-1">{{ $v->creator?->name ?: '—' }}</div><div class="small mt-1">ID #{{ $v->id }} · {{ __('hr.immutable') }}</div></div>
                @empty<div class="text-muted">{{ __('hr.no_records') }}</div>@endforelse
            </div></div>
        </div>
    </div>
</div>
