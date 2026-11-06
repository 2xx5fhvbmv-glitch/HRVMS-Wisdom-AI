<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The WAI Guide (public/resorts_assets/js/wai-guide-content.js) names screens by Laravel route name.
 * A renamed or removed route would leave a guide step pointing nowhere, so every name used there must exist.
 * (node tests/js/wai-guide-content.check.js checks the content itself.)
 */
class WaiGuideContentTest extends TestCase
{
    public function test_every_screen_named_in_the_guides_is_a_real_route(): void
    {
        $file = public_path('resorts_assets/js/wai-guide-content.js');
        preg_match_all("/'([\\w.\\-]+)':\\s*\\['[a-z]+',\\s*'/", file_get_contents($file), $m);
        $names = array_unique($m[1]);

        $this->assertNotEmpty($names, 'No screens found in the guide content.');
        $missing = array_values(array_filter($names, fn ($n) => ! Route::has($n)));
        $this->assertSame([], $missing, 'Guide screens that are not routes: ' . implode(', ', $missing));
    }
}
