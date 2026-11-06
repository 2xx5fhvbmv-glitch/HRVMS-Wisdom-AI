<?php

namespace App\Services\Wisdom;

use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * "How do I … in Wisdom AI?" grounding for WAI.
 *
 * Instead of hand-written how-to docs (which go stale and never cover every
 * module), this reads the screens themselves: for each menu page the user can
 * see in a module, it resolves route → controller action → blade view(s) and
 * pulls out the visible labels (tabs, buttons, modal titles, form fields, table
 * headers) in page order. The model composes steps only from those labels.
 */
class ModuleGuide
{
    /** Labels kept per page — enough for tabs/buttons/forms without flooding the prompt. */
    const MAX_LABELS = 160;

    /** Pages returned with their labels per call (the best matches for the task). */
    const DETAIL_PAGES = 3;

    /** Module names in the logged-in user's navbar (only modules with at least one visible page). */
    public static function modules(): array
    {
        $user = Auth::guard('resort-admin')->user();
        if (!$user) {
            return [];
        }
        return collect(Common::visibleResortMenu($user))
            ->filter(fn ($m) => !empty($m['submenu']))
            ->pluck('ModuleName')->values()->all();
    }

    /** Tool result for get_module_guide. */
    public static function guide(array $args): array
    {
        $user = Auth::guard('resort-admin')->user();
        if (!$user) {
            return ['error' => 'No signed-in user.'];
        }
        $menu = collect(Common::visibleResortMenu($user))->filter(fn ($m) => !empty($m['submenu']));
        $available = $menu->pluck('ModuleName')->values()->all();

        $want = self::norm($args['module'] ?? '');
        $module = $want === '' ? null : ($menu->first(fn ($m) => self::norm($m['ModuleName']) === $want)
            ?? $menu->first(fn ($m) => str_contains(self::norm($m['ModuleName']), $want) || str_contains($want, self::norm($m['ModuleName']))));
        if (!$module) {
            return [
                'error'             => 'That module is not in this user\'s menu.',
                'available_modules' => $available,
                'next_step'         => 'Ask the user which module they mean and end your reply with [[pick_module]].',
            ];
        }

        $pageFilter = self::norm($args['page'] ?? '');
        // Every round of the tool loop re-sends this result, so only the pages that best match the
        // task carry their labels; the rest are listed by menu path (pass `page` to read one of them).
        $words = array_filter(
            array_map(fn ($w) => rtrim($w, 's'), preg_split('/[^a-z0-9]+/', self::norm($args['task'] ?? ''))),
            fn ($w) => strlen($w) >= 3 && !in_array($w, ['how', 'the', 'and', 'add', 'can', 'where', 'what', 'new', 'for', 'with', 'this', 'that'], true)
        );
        $scored = [];
        foreach ($module['submenu'] as $i => $p) {
            if ($pageFilter !== '' && !str_contains(self::norm($p['PageName']), $pageFilter)) {
                continue;
            }
            $labels = self::pageLabels($p['route']);
            $name = self::norm($p['PageName']);
            $text = self::norm(implode(' ', $labels));
            $score = 0;
            foreach ($words as $w) {
                $score += (str_contains($name, $w) ? 5 : 0) + min(substr_count($text, $w), 5);
            }
            $scored[] = ['i' => $i, 'score' => $score, 'path' => $module['ModuleName'] . ' → ' . $p['PageName'], 'labels' => $labels];
        }
        usort($scored, fn ($a, $b) => [$b['score'], $a['i']] <=> [$a['score'], $b['i']]);

        $pages = $other = [];
        foreach ($scored as $n => $s) {
            if ($n < self::DETAIL_PAGES) {
                $pages[] = ['menu_path' => $s['path'], 'screen_text' => implode(' · ', $s['labels'])];
            } else {
                $other[] = $s['path'];
            }
        }

        return [
            'module'    => $module['ModuleName'],
            'pages'     => $pages,
            'other_pages_in_menu' => $other,
            'how_to_read' => 'screen_text = the labels shown on that page, top to bottom (tabs, buttons, pop-up titles, form fields, column headers). Build the steps ONLY from menu_path and these labels. Every button, tab, field or section you name must appear word-for-word in screen_text. If the task is not visible in these labels, say plainly that you could not find where this is done on these screens (it may be automatic or set elsewhere) — never describe a setting, section or button that is not listed.',
        ];
    }

    /** Visible labels of the page behind a named route ([] when it can't be resolved). */
    public static function pageLabels(string $routeName): array
    {
        try {
            $route = Route::getRoutes()->getByName($routeName);
            $action = $route ? $route->getActionName() : '';
            if (!str_contains($action, '@')) {
                return [];
            }
            [$class, $method] = explode('@', $action);
            $ref = new \ReflectionMethod($class, $method);
            $lines = file($ref->getFileName());
            $src = implode('', array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));

            preg_match_all('/view\(\s*[\'"]([\w.\-\/]+)[\'"]/', $src, $m);
            $labels = [];
            $seen = [];
            foreach (array_unique($m[1]) as $view) {
                self::collect($view, $labels, $seen, 0);
            }
            return array_slice(array_values(array_unique($labels)), 0, self::MAX_LABELS);
        } catch (\Throwable $e) {
            Log::info('WAI module guide: page not readable', ['route' => $routeName, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /** Append a blade view's labels (and its @include'd partials, 2 levels deep) to $labels. */
    private static function collect(string $view, array &$labels, array &$seen, int $depth): void
    {
        if ($depth > 2 || isset($seen[$view]) || str_starts_with($view, 'resorts.layouts.')) {
            return;
        }
        $seen[$view] = true;
        try {
            $blade = file_get_contents(view()->getFinder()->find($view));
        } catch (\Throwable $e) {
            return;
        }

        preg_match_all('/@include(?:If)?\(\s*[\'"]([\w.\-\/]+)[\'"]/', $blade, $inc);
        // Page-specific scripts (not libraries) often build the pop-ups/drawers; their HTML labels count too.
        preg_match_all('/[\'"](resorts_assets\/js\/[\w\-\/]+(?<!\.min)\.js)[\'"]/', $blade, $js);

        $html = preg_replace([
            '/\{\{--.*?--\}\}/s',
            '/<!--.*?-->/s',
            '/<script\b.*?<\/script>/is',
            '/<style\b.*?<\/style>/is',
            '/@php\b.*?@endphp/s',
            '/\{!!.*?!!\}/s',
            '/\{\{.*?\}\}/s',
        ], ' ', $blade);
        $html = preg_replace('/@\w+\s*(\((?:[^()]++|(?1))*\))?/', ' ', $html);   // blade directives incl. nested parens
        $html = preg_replace('/<[^>]*>/', "\n", $html);

        foreach (array_unique($js[1]) as $file) {
            if (is_file($path = public_path($file)) && preg_match_all('/>([^<>\'"`{}$;=]{2,200})</', file_get_contents($path), $jm)) {
                $html .= "\n" . implode("\n", $jm[1]);
            }
        }

        foreach (explode("\n", $html) as $line) {
            $t = trim(preg_replace('/\s+/u', ' ', html_entity_decode($line, ENT_QUOTES | ENT_HTML5)));
            if (mb_strlen($t) >= 2 && mb_strlen($t) <= 200 && preg_match('/\p{L}{2}/u', $t)) {
                $labels[] = $t;
            }
        }

        foreach (array_unique($inc[1]) as $child) {
            self::collect($child, $labels, $seen, $depth + 1);
        }
    }

    private static function norm(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', str_replace(['module', '&'], ['', 'and'], strtolower($s))));
    }
}
