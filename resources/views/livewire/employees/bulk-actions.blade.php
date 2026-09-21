<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Bulk employee actions</h1>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('employees.index') }}">Employees</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @error('bulk')<div class="alert alert-danger">{{ $message }}</div>@enderror
    <div class="card mb-3"><div class="card-body row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Action</label><select class="form-select" wire:model.live="action"><option value="organization">Organization assignment</option><option value="status">Employment status</option><option value="salary">Effective-dated salary</option></select></div>
        @if($action==='organization')
            <div class="col-md-3"><label class="form-label">Branch</label><select class="form-select" wire:model="branchId"><option value="">Clear / none</option>@foreach($branches as $row)<option value="{{ $row->id }}">{{ $row->code }} — {{ $row->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Department</label><select class="form-select" wire:model="departmentId"><option value="">Clear / none</option>@foreach($departments as $row)<option value="{{ $row->id }}">{{ $row->code }} — {{ $row->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Cost center</label><select class="form-select" wire:model="costCenterId"><option value="">Clear / none</option>@foreach($costCenters as $row)<option value="{{ $row->id }}">{{ $row->code }} — {{ $row->name }}</option>@endforeach</select></div>
        @elseif($action==='status')
            <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" wire:model="status"><option value="active">Active</option><option value="on_leave">On leave</option><option value="inactive">Inactive</option></select></div>
            <div class="col-md-6"><div class="alert alert-warning py-2 mb-0">Termination is intentionally excluded; use the individual termination workflow so settlement logic is preserved.</div></div>
        @else
            <div class="col-md-2"><label class="form-label">Salary JOD</label><input class="form-control" wire:model="salary" inputmode="decimal"></div>
            <div class="col-md-2"><label class="form-label">Effective from</label><input type="date" class="form-control" wire:model="effectiveFrom"></div>
            <div class="col-md-5"><label class="form-label">Reason</label><input class="form-control" wire:model="reason" maxlength="1000"></div>
        @endif
        <div class="col-12"><button class="btn btn-primary" wire:click="apply">Apply to {{ count($selected) }} selected</button></div>
        @if($errors->any())<div class="col-12"><div class="alert alert-danger py-2 mb-0">{{ $errors->first() }}</div></div>@endif
    </div></div>
    <div class="card"><div class="card-body">
        <div class="row g-2 mb-3"><div class="col-md-6"><input class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search employees"></div><div class="col-md-6 text-md-end"><button class="btn btn-sm btn-outline-primary" wire:click="selectVisible">Select first 100 matching</button> <button class="btn btn-sm btn-outline-secondary" wire:click="clearSelection">Clear</button></div></div>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th></th><th>Name</th><th>National ID</th><th>Status</th><th>Salary</th></tr></thead><tbody>
        @forelse($rows as $employee)<tr><td><input class="form-check-input" type="checkbox" wire:model="selected" value="{{ $employee->id }}"></td><td>{{ $employee->name }}</td><td>{{ $employee->national_id }}</td><td>{{ $employee->status }}</td><td>{{ $employee->salary }} JOD</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No employees</td></tr>@endforelse
        </tbody></table></div>{{ $rows->links() }}
    </div></div>
</div>
