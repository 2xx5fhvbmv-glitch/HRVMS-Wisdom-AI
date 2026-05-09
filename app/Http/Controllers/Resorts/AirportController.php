<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    /**
     * Select2 AJAX search endpoint.
     *
     *   GET /resort/airports/search?q=hani&type=national|international
     *
     * Returns:
     *   { results: [{ id: "HAQ - Hanimaadhoo International Airport",
     *                 text: "HAQ — Hanimaadhoo International Airport (Hanimaadhoo, Maldives)" }, ...],
     *     pagination: { more: false } }
     *
     * - id matches the legacy storage format ("CODE - Name") so existing
     *   leave_destination rows still resolve.
     * - capped at 50 rows so the dropdown stays snappy on the full IATA
     *   dataset (~9000 rows).
     */
    public function search(Request $request)
    {
        $term = (string) $request->query('q', '');
        $type = $request->query('type'); // optional: 'national' | 'international'

        $query = Airport::where('is_active', true)
            ->when($type === 'national' || $type === 'international', fn ($q) => $q->where('type', $type))
            ->search($term)
            // National (Maldives) airports first when no specific type filter,
            // then alphabetic by city/name. Helps the common case of an
            // employee starting with a Maldivian airport.
            ->orderByRaw("CASE WHEN type='national' THEN 0 ELSE 1 END")
            ->orderBy('city')
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'iata_code', 'name', 'city', 'country', 'type']);

        $results = $query->map(function ($a) {
            $loc = trim(($a->city ?? '') . ($a->country ? ', ' . $a->country : ''), ', ');
            return [
                'id'   => $a->iata_code . ' - ' . $a->name,
                'text' => $a->iata_code . ' — ' . $a->name . ($loc ? ' (' . $loc . ')' : ''),
                'group'=> $a->type === 'national' ? 'National (Maldives)' : 'International',
            ];
        })->values();

        // Group results into Select2 optgroups so the UI mirrors the old
        // grouped dropdown.
        $grouped = [];
        foreach ($results as $r) {
            $g = $r['group'];
            if (!isset($grouped[$g])) $grouped[$g] = ['text' => $g, 'children' => []];
            $grouped[$g]['children'][] = ['id' => $r['id'], 'text' => $r['text']];
        }

        return response()->json([
            'results'    => array_values($grouped),
            'pagination' => ['more' => false],
        ]);
    }
}
