<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class OperatorController extends Controller
{
    public function index()
    {
        $this->authorizeOperator();

        return view('operator.dashboard', [
            'tenants' => Tenant::with('domains')->latest()->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeOperator();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sector' => ['nullable', 'string', 'max:255'],
            'ssc_establishment_number' => ['nullable', 'string', 'max:100'],
            'domain' => ['required', 'string', 'max:255', 'regex:/^(?!https?:\/\/)[A-Za-z0-9.-]+$/', Rule::notIn(config('tenancy.central_domains')), 'unique:domains,domain'],
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $tenant = new Tenant([
            'name' => $data['name'],
            'sector' => $data['sector'] ?? null,
            'ssc_establishment_number' => $data['ssc_establishment_number'] ?? null,
            'plan' => 'standard',
            'subscription_status' => 'trial',
        ]);

        try {
            // The TenantCreated listener creates and migrates the physical tenant database.
            $tenant->save();
            $tenant->domains()->create(['domain' => $data['domain']]);
            tenancy()->initialize($tenant);
            User::create([
                'name' => $data['name'].' Admin',
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'role' => 'company_admin',
                'locale' => 'ar',
                'active' => true,
            ]);
            tenancy()->end();
        } catch (Throwable $e) {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            if ($tenant->exists) {
                try {
                    $tenant->delete();
                } catch (Throwable) {
                    // Preserve the original provisioning exception; failed tenant cleanup is logged by Laravel.
                }
            }
            throw $e;
        }

        return back()->with('success', 'Tenant created, migrated, and administrator provisioned.');
    }

    public function updateSubscription(Request $request, Tenant $tenant)
    {
        $this->authorizeOperator();
        $data = $request->validate([
            'plan' => ['required', 'string', 'max:50'],
            'subscription_status' => ['required', 'in:trial,active,past_due,suspended,cancelled'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        $tenant->update($data);

        return back()->with('success', 'Subscription health updated.');
    }

    private function authorizeOperator(): void
    {
        abort_unless(auth('central')->user()?->is_super_admin, 403);
    }
}
