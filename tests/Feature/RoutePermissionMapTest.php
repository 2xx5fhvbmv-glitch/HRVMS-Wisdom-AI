<?php

namespace Tests\Feature;

use App\Console\Commands\GenerateRoutePermissionMap;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * X-01 deny-by-default prep (report-only). Every resort-portal route (checkResortPermission middleware) must be
 * a module_pages row, in config/route_permissions.php's map (hand-written + route_permissions_generated.php),
 * or listed in config/route_permissions_unmapped.php — the documented allow-list of routes the generator could
 * not place. A new route failing here: re-run `php artisan routes:generate-permission-map --write`, or
 * hand-write its entry. Read-only: no DB writes.
 */
class RoutePermissionMapTest extends TestCase
{
    public function test_every_portal_route_is_a_page_row_mapped_or_explicitly_unmapped(): void
    {
        $pages = GenerateRoutePermissionMap::pageRows();
        $map = config('route_permissions.map');
        $unmapped = require config_path('route_permissions_unmapped.php');

        $missing = array_keys(array_filter(GenerateRoutePermissionMap::portalRoutes(),
            fn ($r, $name) => !isset($pages[$name]) && !isset($map[$name]) && !isset($unmapped[$name]), ARRAY_FILTER_USE_BOTH));

        $this->assertSame([], $missing, 'Portal routes with no permission owner — run routes:generate-permission-map --write');
    }

    public function test_every_map_owner_page_exists(): void
    {
        $routes = DB::table('module_pages')->whereNotNull('internal_route')->pluck('internal_route')->flip();
        $bad = [];
        foreach (config('route_permissions.map') as $name => [$owner, $tick]) {
            $ok = $owner === '*' ? $tick === 'any_authenticated'
                : isset($routes[$owner]) && in_array($tick, ['view', 'create', 'edit', 'delete'], true);
            if (!$ok) $bad[] = "$name => [$owner, $tick]";
        }
        $this->assertSame([], $bad);
    }

    public function test_get_routes_never_require_a_write_tick(): void
    {
        $map = config('route_permissions.map');
        $bad = [];
        foreach (app('router')->getRoutes() as $route) {
            $tick = $map[$route->getName() ?? ''][1] ?? null;
            if (in_array('GET', $route->methods(), true) && in_array($tick, ['create', 'edit', 'delete'], true)) {
                $bad[] = $route->getName() . " => $tick";
            }
        }
        $this->assertSame([], $bad);
    }

    public function test_generated_file_is_up_to_date_and_enforcement_stays_off(): void
    {
        $this->assertFalse(config('route_permissions.enforce_unmapped'));

        $built = GenerateRoutePermissionMap::build();
        $this->assertSame($built['generated'], require config_path('route_permissions_generated.php'));
        $this->assertSame(array_keys($built['unmapped']), array_keys(require config_path('route_permissions_unmapped.php')));
    }

    public function test_tick_inference(): void
    {
        $route = fn (string $method, string $name, string $action) => (new \Illuminate\Routing\Route([$method], 'x', ['uses' => "App\\X@$action"]))->name($name);

        $this->assertSame('view', GenerateRoutePermissionMap::inferTick('a.export', $route('GET', 'a.export', 'export')));
        $this->assertSame('view', GenerateRoutePermissionMap::inferTick('a.getList', $route('POST', 'a.getList', 'getList')));
        $this->assertSame('create', GenerateRoutePermissionMap::inferTick('a.store', $route('POST', 'a.store', 'store')));
        $this->assertSame('edit', GenerateRoutePermissionMap::inferTick('a.restore', $route('POST', 'a.restore', 'restore')));
        $this->assertSame('edit', GenerateRoutePermissionMap::inferTick('a.approve', $route('POST', 'a.approve', 'approve')));
        $this->assertSame('delete', GenerateRoutePermissionMap::inferTick('a.massRemove', $route('POST', 'a.massRemove', 'massRemove')));
        $this->assertSame('delete', GenerateRoutePermissionMap::inferTick('a.x', $route('DELETE', 'a.x', 'x')));
    }
}
