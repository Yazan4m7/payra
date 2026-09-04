<?php
namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenancyServiceProvider extends ServiceProvider
{
    public static string $controllerNamespace = '';

    public function events(): array
    {
        return [
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([Jobs\CreateDatabase::class, Jobs\MigrateDatabase::class])
                    ->send(fn (Events\TenantCreated $event) => $event->tenant)
                    ->shouldBeQueued(false),
            ],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                JobPipeline::make([Jobs\DeleteDatabase::class])->send(fn (Events\TenantDeleted $event) => $event->tenant)->shouldBeQueued(false),
            ],
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],
            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],
        ];
    }

    public function register(): void {}

    public function boot(): void
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }

        $this->bootRoutes();
        $this->makeTenancyMiddlewareHighestPriority();
    }

    protected function bootRoutes(): void
    {
        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority(): void
    {
        $middleware = [
            Middleware\PreventAccessFromCentralDomains::class,
            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];
        foreach (array_reverse($middleware) as $class) $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependToMiddlewarePriority($class);
    }
}
