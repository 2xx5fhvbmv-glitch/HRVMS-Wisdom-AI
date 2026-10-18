<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Carbon 3 made every diffIn*() / floatDiffIn*() signed by default (Carbon 2 was
 * absolute), so `$out->diffInMinutes($in)` turned every mobile check-out into 00:00.
 * Every call must say which it wants — `(int) $a->diffInX($b, true)` behaves the
 * same on Carbon 2 and 3; pass `false` where the sign is meant.
 */
class CarbonDiffCallsTest extends TestCase
{
    public function test_every_diff_call_states_absolute_or_signed()
    {
        $bad = [];
        $files = array_filter(explode("\n", (string) shell_exec('cd ' . escapeshellarg(base_path()) . " && grep -rlE 'iffIn[A-Za-z]*\\(' app routes resources/views database/seeders")));
        foreach ($files as $rel) {
            $src = file_get_contents(base_path($rel));
            $chunks = str_ends_with($rel, '.blade.php')
                ? array_map(fn ($l) => '<?php ' . $l, explode("\n", $src)) // view @php code is single-line
                : [$src];
            foreach ($chunks as $code) {
                $t = token_get_all($code);
                foreach ($t as $i => $tok) {
                    if (!is_array($tok) || $tok[0] !== T_STRING || !preg_match('/^(float)?[dD]iffIn\w+$/', $tok[1])) {
                        continue;
                    }
                    $p = $i - 1;
                    while (is_array($t[$p]) && $t[$p][0] === T_WHITESPACE) $p--;
                    if (!is_array($t[$p]) || !in_array($t[$p][0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
                        continue;
                    }
                    $depth = 0;
                    $commas = 0;
                    for ($k = $i + 1; isset($t[$k]); $k++) {
                        $s = is_array($t[$k]) ? $t[$k][1] : $t[$k];
                        if (in_array($s, ['(', '[', '{'], true) || (is_array($t[$k]) && $t[$k][0] === T_CURLY_OPEN)) $depth++;
                        elseif (in_array($s, [')', ']', '}'], true) && --$depth === 0) break;
                        elseif ($depth === 1 && $s === ',') $commas++;
                    }
                    if ($commas < 1) {
                        $bad[] = $rel . ':' . ($tok[2] ?? '?') . ' ' . $tok[1] . '()';
                    }
                }
            }
        }
        $this->assertSame([], $bad, "diffIn*() without an explicit absolute/signed flag (signed on Carbon 3):\n" . implode("\n", $bad));
    }
}
