<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * READ-ONLY X-01 rollout aid: simulates `route_permissions.enforce_unmapped = true` without reading logs or
 * touching the flag. For each resort and each position with active (non-super) portal logins it counts the
 * portal routes that would then be DENIED — routes without their own module_pages row that are either
 * unmapped, or mapped to an owner page whose required tick (view/create/edit/delete) the position lacks.
 * Routes with their own module_pages row are already enforced today and are not counted. Also summarises
 * which owner pages/ticks cause the denials, so HR knows what to tick, and counts the report-only log lines.
 *
 *   php artisan routes:permission-report
 *   php artisan routes:permission-report --resort=28
 *   php artisan routes:permission-report --resort=28 --position=139 --limit=100
 *
 * Simplification: a tick is a resort_interal_pages_permissions row for (resort, position, page, permission)
 * with a resort_pagewise_permissions row for that resort/module/page — Common::CheckResortPermissions'
 * extra Dept_id condition is not modelled.
 */
class RoutePermissionReport extends Command
{
    protected $signature = 'routes:permission-report {--resort= : Only this resort id} {--position= : List the denied routes for this position id} {--limit=50 : Rows to list}';
    protected $description = 'Read-only: routes each position would lose if route_permissions.enforce_unmapped were true.';

    public function handle(): int
    {
        $routes = array_keys(GenerateRoutePermissionMap::portalRoutes());
        $pages = GenerateRoutePermissionMap::pageRows();
        $map = config('route_permissions.map', []);
        $bypass = ['resort.Page.Permission', 'resort.getMenuData'];   // as Common::checkRouteWisePermission
        $permIds = config('settings.resort_permissions');
        $modules = DB::table('modules')->pluck('module_name', 'id');
        $limit = (int) $this->option('limit');

        // Owner rows as checkRoutePermissionsMap() finds them: ModulePages::where('internal_route', ...)->first().
        $owners = [];
        foreach (DB::table('module_pages')->whereNotNull('internal_route')->orderBy('id')->get(['id', 'Module_Id', 'page_name', 'internal_route']) as $p) {
            $owners[$p->internal_route] ??= $p;
        }

        // Each checked route => [owner page row|null, permission id|null, reason when it can never pass].
        $checks = [];
        foreach ($routes as $name) {
            if (isset($pages[$name]) || in_array($name, $bypass, true)) continue;
            $entry = $map[$name] ?? null;
            if ($entry === null) { $checks[$name] = [null, null, 'unmapped']; continue; }
            [$owner, $tick] = $entry;
            if ($owner === '*' && $tick === 'any_authenticated') continue;
            if (!isset($owners[$owner])) { $checks[$name] = [null, null, 'bad owner page']; continue; }
            $checks[$name] = [$owners[$owner], $permIds[$tick] ?? $permIds['view'], null];
        }
        $this->info(count($routes) . ' portal routes; ' . count($checks) . ' depend on the flag (' . count(array_filter($checks, fn ($c) => $c[2] === 'unmapped')) . ' unmapped).');

        $resorts = DB::table('employees as e')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')
            ->where('e.status', 'Active')->where('a.type', '!=', 'super')   // super admins bypass every check
            ->when($this->option('resort'), fn ($q, $id) => $q->where('e.resort_id', $id))
            ->select('e.resort_id', 'e.Position_id', DB::raw('count(*) as employees'))
            ->groupBy('e.resort_id', 'e.Position_id')->get()->groupBy('resort_id');
        $tickName = array_flip($permIds);

        foreach ($resorts as $resortId => $positions) {
            $titles = DB::table('resort_positions')->whereIn('id', $positions->pluck('Position_id'))->pluck('position_title', 'id');
            $this->newLine();
            $this->info("Resort $resortId — " . DB::table('resorts')->where('id', $resortId)->value('resort_name'));

            $rows = $byPage = $denied = [];
            foreach ($positions as $pos) {
                $ticks = DB::table('resort_interal_pages_permissions as t')
                    ->join('resort_pagewise_permissions as p', fn ($j) => $j->on('p.page_permission_id', '=', 't.page_id')->where('p.resort_id', $resortId))
                    ->where('t.resort_id', $resortId)->where('t.position_id', $pos->Position_id)
                    ->get(['p.Module_id', 't.page_id', 't.Permission_id'])
                    ->mapWithKeys(fn ($t) => ["$t->Module_id|$t->page_id|$t->Permission_id" => true])->all();

                $unmapped = $noTick = 0;
                foreach ($checks as $name => [$page, $permId, $reason]) {
                    if ($reason === null && isset($ticks["$page->Module_Id|$page->id|$permId"])) continue;
                    if ($reason !== null) {
                        $unmapped++;
                        $key = "(none) — $reason";
                    } else {
                        $noTick++;
                        $key = ($modules[$page->Module_Id] ?? '?') . " › $page->page_name [{$tickName[$permId]}]";
                    }
                    $byPage[$key]['routes'][$name] = true;
                    $byPage[$key]['positions'][$pos->Position_id] = true;
                    if ((string) $pos->Position_id === (string) $this->option('position')) $denied[] = [$name, $key];
                }
                $rows[] = [$pos->Position_id, $titles[$pos->Position_id] ?? '?', $pos->employees, count($ticks), $unmapped + $noTick, $unmapped, $noTick];
            }
            usort($rows, fn ($a, $b) => $b[4] <=> $a[4]);
            $this->table(['position', 'title', 'active logins', 'ticks', 'would deny', 'unmapped', 'missing tick'], $rows);

            uasort($byPage, fn ($a, $b) => [count($b['positions']), count($b['routes'])] <=> [count($a['positions']), count($a['routes'])]);
            $this->line('Owner pages/ticks behind the denials, top ' . min($limit, count($byPage)) . ' of ' . count($byPage) . ' (tick these on the Permissions screen):');
            $this->table(['module › page [tick]', 'routes', 'positions affected'], array_slice(array_map(
                fn ($k, $v) => [$k, count($v['routes']), count($v['positions'])], array_keys($byPage), $byPage), 0, $limit));

            if ($this->option('position') !== null) {
                $this->line("Position {$this->option('position')}: " . count($denied) . ' routes would be denied' . ($denied ? " (first $limit):" : '.'));
                if ($denied) $this->table(['route', 'needs'], array_slice($denied, 0, $limit));
            }
        }

        $this->logCounts();
        return self::SUCCESS;
    }

    /** UNMAPPED_ROUTE / ROUTE_PERMISSIONS_WOULD_DENY lines from the last 7 days of storage/logs/laravel*.log (grep only). */
    private function logCounts(): void
    {
        $files = glob(storage_path('logs/laravel*.log')) ?: [];
        if (!$files) {
            $this->line('No storage/logs/laravel*.log present.');
            return;
        }
        $since = now()->subDays(7)->format('Y-m-d');
        $count = [];
        foreach ($files as $file) {
            $fh = fopen($file, 'r');
            while (($line = fgets($fh)) !== false) {
                if (!preg_match('/^\[(\d{4}-\d{2}-\d{2})[^\]]*\] (\w+)\.WARNING: (UNMAPPED_ROUTE|ROUTE_PERMISSIONS_WOULD_DENY) /', $line, $m) || $m[1] < $since) continue;
                $count["$m[3] ($m[2])"] = ($count["$m[3] ($m[2])"] ?? 0) + 1;
            }
            fclose($fh);
        }
        $this->newLine();
        $this->line('Report-only log lines since ' . $since . ' (by env): ' . ($count ? json_encode($count) : 'none'));
    }
}
