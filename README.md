# Jordan Payroll & HR Compliance SaaS

Standalone **Laravel 11 + Livewire 3 + stancl/tenancy v3** multi-tenant payroll/HR SaaS for Jordan, with full Arabic RTL and English UI.

## v1 included

- Database-per-tenant company isolation.
- Central SaaS operator console for tenant/subscription health only.
- Company admin, HR, and employee roles.
- Employee master: national ID, SSC number + enrollment date, hire date, title, salary, IBAN, status, portal account.
- Versioned, effective-dated compliance settings. Legal values are never constants in payroll/leave/overtime/termination code.
- Payroll runs and bilingual payslip PDFs.
- SSC employee/employer contributions with separate pre/post-cutoff ceilings selected from the configured SSC enrollment cutoff date.
- Progressive income tax, personal exemption, and high-earner surcharge driven by the selected compliance version.
- Approved overtime with configurable standard/rest-day/holiday multipliers and daily/weekly/monthly caps.
- Annual/sick leave balances, request/approval workflow, holiday/rest-day-aware day counting, hospitalization extra entitlement.
- Termination settlement: notice shortfall/pay-in-lieu, final-month salary, unused annual leave payout.
- Onboarding SSC registration task due on hire date and compliance alerts until both SSC number and enrollment date are recorded.
- Admin-editable public holiday calendar per year.
- Compliance dashboard: stale settings, missing holiday calendar, overdue onboarding/SSC, overtime cap warnings, filing-deadline definitions.
- Employee self-service: leave, overtime requests, balances, payslip downloads and deduction breakdown.
- Bank-transfer CSV export with IBAN + net JOD.
- Company profile settings and central operator subscription status controls.

Recruiting, performance reviews, and attendance-hardware integrations are intentionally outside v1.

## Isolation model

The **central database** contains only:

- `tenants`
- `domains`
- `central_users`

Every company gets its own physical database containing users, employees, payroll, payslips, leave, overtime, compliance versions, holidays, onboarding and termination data. There is deliberately **no `tenant_id` discriminator** on HR/business tables.

Tenant routes use, in order:

1. `InitializeTenancyByDomain`
2. `PreventAccessFromCentralDomains`
3. `ScopeSessions`
4. tenant authentication/role authorization

`CacheTenancyBootstrapper`, filesystem tenancy and queue tenancy are enabled. Redis is the default cache store because stancl's cache bootstrapper requires a cache store with tag support.

## JOD arithmetic

Money columns are `DECIMAL(...,3)`. The calculation layer uses `brick/math` `BigDecimal`; `App\Support\Money` rejects PHP float input. Currency values should cross controller/service boundaries as decimal strings.

## Server requirements

- PHP 8.2+
- MySQL 8+ or PostgreSQL with a tenant database manager configuration
- PHP extensions required by Laravel plus `pdo_mysql`/database driver
- Redis + `phpredis` for tenant-scoped cache in the provided production configuration
- Node.js 20+ for Vite assets
- A DB user allowed to create/drop tenant databases
- HTTPS in production

## Installation

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
```

Configure the central operator before seeding:

```env
OPERATOR_NAME="SaaS Operator"
OPERATOR_EMAIL="admin@example.com"
OPERATOR_PASSWORD="use-a-long-unique-password"
CENTRAL_DOMAIN=payroll.example.com
```

For production also use:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
CACHE_STORE=redis
QUEUE_CONNECTION=database
```

Point tenant domains/subdomains to the same Laravel deployment. The operator console is:

```text
https://<CENTRAL_DOMAIN>/operator
```

Creating a company creates the tenant record, physical tenant database, runs `database/migrations/tenant`, attaches its domain, and provisions the first `company_admin` account.

## Compliance setup — required before payroll

The repository intentionally does **not** seed legal rates/ceilings/thresholds. Copy or load `COMPLIANCE-SETTINGS-TEMPLATE.json`, fill it from verified current Jordanian requirements, then save it as an immutable effective-dated version.

Required settings include:

- `ssc_employee_percent`
- `ssc_employer_percent`
- `ssc_enrollment_cutoff_date`
- `ssc_ceiling_pre_cutoff_jod`
- `ssc_ceiling_post_cutoff_jod`
- `income_tax_brackets[]`
- `personal_exemption_annual_jod`
- `high_earner_surcharge_threshold_annual_jod`
- `high_earner_surcharge_percent`
- `annual_leave_tiers[]`
- `sick_leave_paid_days`
- `sick_leave_hospital_extra_days`
- overtime multipliers, warning percentage and optional daily/weekly/monthly caps
- `notice_period_days`
- `monthly_hours_divisor`
- `salary_daily_divisor`
- weekly rest days and holiday/rest-day leave-counting rules
- tenant-specific filing deadline definitions

The first annual-leave tier must start at zero years, brackets must be strictly increasing with a final open-ended bracket, and percentages/divisors/caps are validated before a version can be stored.

Compliance versions are immutable. Existing payroll runs keep both the foreign key to the selected version and a full calculation snapshot, so adding a later legal amendment cannot silently rewrite historical payslips.

## Payroll calculation flow

For each eligible employee in a run:

1. Select the compliance version effective at month-end.
2. Compare SSC enrollment date with the configurable cutoff date.
3. Cap SSC base at the corresponding configured ceiling.
4. Calculate employee and employer SSC separately.
5. Add approved overtime using the configured hourly divisor and multiplier.
6. Annualize taxable income, subtract the configured personal exemption, apply progressive brackets and configured high-earner surcharge.
7. Convert annual withholding to the month.
8. `net = gross + overtime - employee SSC - income tax - surcharge`.
9. Persist a full settings/calculation snapshot on the payslip.

Employer SSC is reported but is not deducted from employee net pay.

## Leave / overtime / termination

- Leave balances are created per employee/year from the compliance version effective at the start of that year and store the settings-version reference.
- Leave approval counts days according to configured weekly rest days and the tenant's public-holiday calendar, then deducts the appropriate balance transactionally.
- Overlapping pending/approved leave requests are blocked.
- Overtime rate type is stored symbolically (`standard` or `rest_holiday`), while the actual legal multiplier remains versioned in compliance settings. Rest/holiday classification is derived from date + configured rest days + holiday calendar.
- Overtime caps are enforced on approval, with dashboard/self-service warnings as the configured threshold is approached.
- Termination uses the configured notice period and salary daily divisor; the calculation snapshot is retained and the employee portal is disabled after termination.

## Queue / scheduler

Production should run workers continuously:

```bash
php artisan queue:work --tries=3
php artisan schedule:work
```

The yearly `hr:accrue-leave` task loops **all tenants explicitly**, initializes each tenant database, creates the new year's balances, and ends tenancy before moving to the next company. You can target specific tenants:

```bash
php artisan hr:accrue-leave 2027 --tenant=<tenant-uuid>
```

## Tests

```bash
php artisan test
npm run build
```

Included coverage includes:

- decimal-safe JOD arithmetic and float rejection
- progressive tax calculation using arbitrary configurable test brackets
- pre/post-cutoff SSC ceiling selection
- compliance payload validation
- legal-number/settings source contracts
- tenant-vs-central migration separation
- tenant route/session scoping contracts
- physical database isolation integration test between two tenants
- payroll source contracts and historical settings snapshot requirement

`.github/workflows/tests.yml` provisions MySQL + Redis and runs backend tests plus the Vite production build.

## Security notes

- Do not set `SESSION_DOMAIN` to a parent domain shared by all tenants unless you fully understand the session implications; the supplied config uses host-only cookies plus `ScopeSessions`.
- Keep `APP_DEBUG=false` in production.
- Use encrypted backups, restricted DB credentials and TLS.
- Treat national IDs, SSC numbers, IBANs, salary and payslips as sensitive personal data in logs, exports and support tooling.
- The central operator UI intentionally does not query tenant employee/payroll tables.
- Tenant provisioning requires DB create/drop privileges; use a dedicated database account restricted to the tenant database prefix where your infrastructure supports it.

## Project structure

```text
app/Models                  Central + tenant Eloquent models
app/Services                Payroll/compliance/leave/overtime/termination logic
app/Livewire                Tenant admin + employee self-service UI
app/Support                 Decimal-safe money helpers
database/migrations         Central schema
database/migrations/tenant  Physical tenant schema
routes/web.php              Central operator routes
routes/tenant.php           Tenant-domain routes
resources/lang/ar|en        Arabic/English translations
resources/views             Livewire, auth, operator and PDF views
tests                       Unit + feature + isolation tests
```
