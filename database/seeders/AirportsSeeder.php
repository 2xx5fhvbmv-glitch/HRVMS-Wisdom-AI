<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AirportsSeeder extends Seeder
{
    /**
     * Seeds the `airports` table.
     *
     * Source priority:
     *   1. database/seeders/data/airports.csv  — full IATA dataset, dropped in by the user.
     *      Expected columns (with or without header):
     *         iata,name,country,city  (matches the user-pasted IATA list)
     *      Optional columns: icao, type, is_active.
     *   2. config/airports.php  — the curated Maldives + South Asia list,
     *      used as a fallback so the dropdown still works before the CSV
     *      is dropped in.
     *
     * Idempotent: uses upsert against iata_code, so re-running the seeder
     * after editing the CSV is safe.
     */
    public function run()
    {
        $csv = database_path('seeders/data/airports.csv');
        if (file_exists($csv)) {
            $this->seedFromCsv($csv);
            return;
        }

        $this->command->warn("airports.csv not found at {$csv}; falling back to config/airports.php.");
        $this->seedFromConfig();
    }

    private function seedFromCsv(string $path): void
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->command->error("Unable to open {$path}");
            return;
        }

        // Auto-detect comma vs tab — the IATA paste format is TSV but
        // some sources are CSV. Whichever has more occurrences in the
        // first line wins.
        $sniff = fgets($handle) ?: '';
        rewind($handle);
        $delim = substr_count($sniff, "\t") > substr_count($sniff, ',') ? "\t" : ',';

        // Maldivian airports — anything matching this list (or country=Maldives)
        // is tagged 'national'; everything else 'international'.
        $maldivesIata = ['MLE','GAN','HAQ','VAM','FVM','KDM','KDO','DRV','TMF','IFU','VRG','GKK','NMF'];

        // Sniff for a header row by checking if the first cell looks
        // like an IATA code (3 letters).
        $first = fgetcsv($handle, 0, $delim);
        $hasHeader = !preg_match('/^[A-Z]{3}$/', strtoupper($first[0] ?? ''));
        if (!$hasHeader && $first) {
            // First row is data, not a header — push it back into the loop.
            $this->insertRow($first, $maldivesIata);
        }

        $batch = [];
        $now   = now();
        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            if (empty($row[0])) continue;
            $iata = strtoupper(trim((string) $row[0]));
            if (!preg_match('/^[A-Z]{3}$/', $iata)) continue;

            $name    = trim((string) ($row[1] ?? ''));
            $country = trim((string) ($row[2] ?? ''));
            $city    = trim((string) ($row[3] ?? ''));
            $type    = (in_array($iata, $maldivesIata, true) || strcasecmp($country, 'Maldives') === 0)
                ? 'national' : 'international';

            $batch[] = [
                'iata_code'  => $iata,
                'name'       => $name,
                'city'       => $city ?: null,
                'country'    => $country ?: null,
                'type'       => $type,
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Flush in chunks so we don't blow up memory on the full IATA
            // dataset (~9000 rows). 500 per upsert is a good balance.
            if (count($batch) >= 500) {
                DB::table('airports')->upsert($batch, ['iata_code'], ['name','city','country','type','is_active','updated_at']);
                $batch = [];
            }
        }
        if (!empty($batch)) {
            DB::table('airports')->upsert($batch, ['iata_code'], ['name','city','country','type','is_active','updated_at']);
        }
        fclose($handle);

        $count = DB::table('airports')->count();
        $this->command->info("Airports seeded from CSV: {$count} total rows.");
    }

    private function insertRow(array $row, array $maldivesIata): void
    {
        $iata = strtoupper(trim((string) ($row[0] ?? '')));
        if (!preg_match('/^[A-Z]{3}$/', $iata)) return;
        $name    = trim((string) ($row[1] ?? ''));
        $country = trim((string) ($row[2] ?? ''));
        $city    = trim((string) ($row[3] ?? ''));
        $type    = (in_array($iata, $maldivesIata, true) || strcasecmp($country, 'Maldives') === 0)
            ? 'national' : 'international';
        DB::table('airports')->upsert([[
            'iata_code'  => $iata,
            'name'       => $name,
            'city'       => $city ?: null,
            'country'    => $country ?: null,
            'type'       => $type,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['iata_code'], ['name','city','country','type','is_active','updated_at']);
    }

    private function seedFromConfig(): void
    {
        $airports = config('airports', ['national' => [], 'international' => []]);
        $rows = [];
        $now  = now();

        foreach (($airports['national'] ?? []) as $a) {
            $rows[] = $this->mapConfigRow($a, 'national', $now);
        }
        foreach (($airports['international'] ?? []) as $a) {
            $rows[] = $this->mapConfigRow($a, 'international', $now);
        }
        $rows = array_values(array_filter($rows));
        if (empty($rows)) {
            $this->command->warn('No airports to seed from config either.');
            return;
        }
        DB::table('airports')->upsert($rows, ['iata_code'], ['name','city','country','type','is_active','updated_at']);
        $this->command->info('Airports seeded from config: ' . count($rows) . ' rows.');
    }

    private function mapConfigRow(array $a, string $type, $now): ?array
    {
        if (empty($a['code']) || empty($a['name'])) return null;
        return [
            'iata_code'  => strtoupper($a['code']),
            'name'       => $a['name'],
            'city'       => $a['city']    ?? null,
            'country'    => $a['country'] ?? ($type === 'national' ? 'Maldives' : null),
            'type'       => $type,
            'is_active'  => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
