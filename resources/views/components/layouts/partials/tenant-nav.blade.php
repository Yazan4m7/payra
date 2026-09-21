<nav class="nav flex-column gap-1">
    @if(auth()->user()->isHr())
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">{{ __('hr.dashboard') }}</a>

        @can('employees.manage')
            <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">{{ __('hr.employees') }}</a>
            <a class="nav-link {{ request()->routeIs('salaries.*') ? 'active' : '' }}" href="{{ route('salaries.index') }}">{{ __('payroll.salary_history') }}</a>
            <a class="nav-link {{ request()->routeIs('earnings.*') ? 'active' : '' }}" href="{{ route('earnings.index') }}">{{ __('payroll.earnings') }}</a>
            <a class="nav-link {{ request()->routeIs('deductions.*') ? 'active' : '' }}" href="{{ route('deductions.index') }}">{{ __('payroll.deductions') }}</a>
            <a class="nav-link {{ request()->routeIs('loans.*') ? 'active' : '' }}" href="{{ route('loans.index') }}">{{ __('payroll.loans') }}</a>
            <a class="nav-link {{ request()->routeIs('terminations.*') ? 'active' : '' }}" href="{{ route('terminations.index') }}">{{ __('hr.terminations') }}</a>
        @endcan

        @can('organization.manage')<a class="nav-link {{ request()->routeIs('organization.*') ? 'active' : '' }}" href="{{ route('organization.structure') }}">Organization</a>@endcan
        @can('contracts.manage')<a class="nav-link {{ request()->routeIs('contracts.*') ? 'active' : '' }}" href="{{ route('contracts.index') }}">Contracts</a>@endcan
        @can('documents.manage')<a class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">Documents</a>@endcan
        @can('imports.manage')<a class="nav-link {{ request()->routeIs('imports.*') ? 'active' : '' }}" href="{{ route('imports.center') }}">Imports</a>@endcan
        @can('recruiting.manage')<a class="nav-link {{ request()->routeIs('recruiting.*') ? 'active' : '' }}" href="{{ route('recruiting.index') }}">Recruiting</a>@endcan
        @can('performance.manage')<a class="nav-link {{ request()->routeIs('performance.index') ? 'active' : '' }}" href="{{ route('performance.index') }}">Performance</a>@endcan

        @can('attendance.manage')
            <a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}">Attendance</a>
            <a class="nav-link {{ request()->routeIs('absences.*') ? 'active' : '' }}" href="{{ route('absences.index') }}">{{ __('payroll.absences') }}</a>
            <a class="nav-link {{ request()->routeIs('leave.*') ? 'active' : '' }}" href="{{ route('leave.requests') }}">{{ __('hr.leave') }}</a>
            <a class="nav-link {{ request()->routeIs('overtime.*') ? 'active' : '' }}" href="{{ route('overtime.entries') }}">{{ __('hr.overtime') }}</a>
            <a class="nav-link {{ request()->routeIs('holidays.*') ? 'active' : '' }}" href="{{ route('holidays.index') }}">{{ __('hr.holidays') }}</a>
        @endcan
        @can('biometric.manage')<a class="nav-link {{ request()->routeIs('biometric.*') ? 'active' : '' }}" href="{{ route('biometric.devices') }}">Biometric devices</a>@endcan

        @can('adjustments.manage')<a class="nav-link {{ request()->routeIs('adjustments.*') ? 'active' : '' }}" href="{{ route('adjustments.index') }}">{{ __('payroll.adjustments') }}</a>@endcan
        @if(auth()->user()->can('payroll.calculate') || auth()->user()->can('payroll.approve') || auth()->user()->can('payroll.export'))
            <a class="nav-link {{ request()->routeIs('payroll.*') ? 'active' : '' }}" href="{{ route('payroll.runs') }}">{{ __('hr.payroll') }}</a>
        @endif
        @can('accounting.export')<a class="nav-link {{ request()->routeIs('accounting.*') ? 'active' : '' }}" href="{{ route('accounting.mapping') }}">Accounting export</a>@endcan
        @can('compliance.manage')<a class="nav-link {{ request()->routeIs('compliance.*') ? 'active' : '' }}" href="{{ route('compliance.settings') }}">{{ __('hr.compliance_settings') }}</a>@endcan
        @can('audit.view')<a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}">Audit log</a>@endcan

        @can('company-admin')
            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">{{ __('hr.users') }}</a>
            <a class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}">Permissions</a>
            <a class="nav-link {{ request()->routeIs('company.*') ? 'active' : '' }}" href="{{ route('company.settings') }}">{{ __('hr.company_settings') }}</a>
        @endcan
    @endif

    @if(auth()->user()->employee)
        <a class="nav-link {{ request()->routeIs('self-service') ? 'active' : '' }}" href="{{ route('self-service') }}">{{ __('hr.self_service') }}</a>
        <a class="nav-link {{ request()->routeIs('performance.my') ? 'active' : '' }}" href="{{ route('performance.my') }}">My performance</a>
    @endif
</nav>
