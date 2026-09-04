<div class="card"><div class="card-body" style="max-width:850px">
    <h2 class="h5">{{ __('hr.company_settings') }}</h2>
    <div class="row g-3 mt-1">
        <div class="col-md-6"><label class="form-label">{{ __('hr.company_name') }}</label><input class="form-control" wire:model="name"></div>
        <div class="col-md-6"><label class="form-label">{{ __('hr.sector') }}</label><input class="form-control" wire:model="sector"></div>
        <div class="col-md-6"><label class="form-label">{{ __('hr.ssc_establishment_number') }}</label><input class="form-control" wire:model="ssc_establishment_number"></div>
        <div class="col-12"><button class="btn btn-primary" wire:click="save">{{ __('hr.save') }}</button></div>
    </div>
    @if($errors->any())<div class="alert alert-danger py-2 mt-3">{{ $errors->first() }}</div>@endif
</div></div>
