<nav class="nav flex-column gap-1">
    @if(auth()->user()->isHr())
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">{{ __('hr.dashboard') }}</a>
        <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">{{ __('hr.employees') }}</a>
        <a class="nav-link {{ request()->routeIs('earnings.*') ? 'active' : '' }}" href="{{ route('earnings.index') }}">{{ __('payroll.earnings') }}</a>
        <a class="nav-link {{ request()->routeIs('deductions.*') ? 'active' : '' }}" href="{{ route('deductions.index') }}">{{ __('payroll.deductions') }}</a>
        <a class="nav-link {{ request()->routeIs('payroll.*') ? 'active' : '' }}" href="{{ route('payroll.runs') }}">{{ __('hr.payroll') }}</a>
        <a class="nav-link {{ request()->routeIs('leave.*') ? 'active' : '' }}" href="{{ route('leave.requests') }}">{{ __('hr.leave') }}</a>
        <a class="nav-link {{ request()->routeIs('overtime.*') ? 'active' : '' }}" href="{{ route('overtime.entries') }}">{{ __('hr.overtime') }}</a>
        <a class="nav-link {{ request()->routeIs('terminations.*') ? 'active' : '' }}" href="{{ route('terminations.index') }}">{{ __('hr.terminations') }}</a>
        <a class="nav-link {{ request()->routeIs('holidays.*') ? 'active' : '' }}" href="{{ route('holidays.index') }}">{{ __('hr.holidays') }}</a>
        <a class="nav-link {{ request()->routeIs('compliance.*') ? 'active' : '' }}" href="{{ route('compliance.settings') }}">{{ __('hr.compliance_settings') }}</a>
        @if(auth()->user()->role === 'company_admin')
            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">{{ __('hr.users') }}</a>
            <a class="nav-link {{ request()->routeIs('company.*') ? 'active' : '' }}" href="{{ route('company.settings') }}">{{ __('hr.company_settings') }}</a>
        @endif
    @endif
    @if(auth()->user()->employee)
        <a class="nav-link {{ request()->routeIs('self-service') ? 'active' : '' }}" href="{{ route('self-service') }}">{{ __('hr.self_service') }}</a>
    @endif
</nav>
