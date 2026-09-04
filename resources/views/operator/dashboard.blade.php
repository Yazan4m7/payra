<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">@vite(['resources/css/app.scss','resources/js/app.js'])<title>Tenant Health</title></head>
<body class="bg-body-tertiary"><div class="container py-4">
    <div class="d-flex justify-content-between align-items-center"><div><h1 class="h3 mb-0">Tenant / Subscription Health</h1><div class="text-muted">Central operator console — tenant HR data is not queried here.</div></div><form method="POST" action="{{ route('operator.logout') }}">@csrf<button class="btn btn-outline-secondary">Logout</button></form></div>
    @if(session('success'))<div class="alert alert-success mt-3">{{session('success')}}</div>@endif
    @if($errors->any())<div class="alert alert-danger mt-3">{{$errors->first()}}</div>@endif

    <div class="card my-4"><div class="card-body"><h2 class="h5">Create company</h2><form class="row g-2" method="POST" action="{{route('operator.tenants.store')}}">@csrf
        <div class="col-md-3"><input class="form-control" name="name" placeholder="Company name" required></div><div class="col-md-2"><input class="form-control" name="sector" placeholder="Sector"></div><div class="col-md-3"><input class="form-control" name="ssc_establishment_number" placeholder="SSC establishment #"></div><div class="col-md-4"><input class="form-control" name="domain" placeholder="company.example.com" required></div>
        <div class="col-md-4"><input class="form-control" name="admin_email" type="email" placeholder="Admin email" required></div><div class="col-md-4"><input class="form-control" name="admin_password" type="password" placeholder="Admin password" minlength="8" required></div><div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
    </form></div></div>

    <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Company</th><th>Domain</th><th>SSC establishment</th><th>Subscription</th></tr></thead><tbody>
    @foreach($tenants as $tenant)<tr><td><strong>{{$tenant->name}}</strong><div class="small text-muted">{{$tenant->sector}}</div></td><td>{{$tenant->domains->pluck('domain')->join(', ')}}</td><td>{{$tenant->ssc_establishment_number ?: '—'}}</td><td style="min-width:420px"><form method="POST" action="{{ route('operator.tenants.subscription',$tenant) }}" class="row g-1">@csrf @method('PATCH')<div class="col-4"><input class="form-control form-control-sm" name="plan" value="{{$tenant->plan}}"></div><div class="col-4"><select class="form-select form-select-sm" name="subscription_status">@foreach(['trial','active','past_due','suspended','cancelled'] as $status)<option value="{{$status}}" @selected($tenant->subscription_status===$status)>{{$status}}</option>@endforeach</select></div><div class="col-3"><input class="form-control form-control-sm" type="date" name="subscription_ends_at" value="{{$tenant->subscription_ends_at?->toDateString()}}"></div><div class="col-1"><button class="btn btn-sm btn-outline-primary">✓</button></div></form></td></tr>@endforeach
    </tbody></table></div></div><div class="mt-3">{{$tenants->links()}}</div>
</div></body></html>
