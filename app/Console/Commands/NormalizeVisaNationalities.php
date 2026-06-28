<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Normalise visa_nationalities.nationality from country NAMES (how the table was
 * seeded — "Bangladesh", "India", "Philippines") to the canonical DEMONYM the
 * config dropdown uses ("Bangladeshi", "Indian", "Filipino"). The edit form only
 * matches demonyms, which is why editing a row silently "changed" the name.
 *
 * Safe: only rewrites a row when the mapped demonym is a real entry in
 * config('settings.nationalities'); rows already holding a valid demonym are left
 * alone. If the target demonym already exists for that resort, the duplicate
 * country-named row is deleted instead of updated (avoids the unique violation).
 *
 *   php artisan visa:normalize-nationalities --dry-run
 *   php artisan visa:normalize-nationalities
 *   php artisan visa:normalize-nationalities --resort=26
 */
class NormalizeVisaNationalities extends Command
{
    protected $signature = 'visa:normalize-nationalities
                            {--resort= : Limit to a single resort_id}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Fix visa_nationalities rows stored as country names -> canonical demonym';

    /** Country name (lower-case) => demonym as spelled in config(settings.nationalities). */
    private function countryToDemonym(): array
    {
        $map = [
            'algeria' => 'Algerian', 'andorra' => 'Andorran', 'antigua and barbuda' => 'Antiguan and Barbudan',
            'argentina' => 'Argentine', 'armenia' => 'Armenian', 'australia' => 'Australian', 'austria' => 'Austrian',
            'azerbaijan' => 'Azerbaijani', 'bahamas' => 'Bahamian', 'bahrain' => 'Bahraini', 'bangladesh' => 'Bangladeshi',
            'barbados' => 'Barbadian', 'belarus' => 'Belarusian', 'belgium' => 'Belgian', 'belize' => 'Belizean',
            'benin' => 'Beninese', 'bhutan' => 'Bhutanese', 'bolivia' => 'Bolivian',
            'bosnia-herzegovina' => 'Bosnian and Herzegovinian', 'bosnia and herzegovina' => 'Bosnian and Herzegovinian',
            'botswana' => 'Botswanan', 'brazil' => 'Brazilian', 'brunei' => 'Bruneian', 'bulgaria' => 'Bulgarian',
            'burkina faso' => 'Burkinabé', 'burundi' => 'Burundian', 'cambodia' => 'Cambodian', 'cameroon' => 'Cameroonian',
            'canada' => 'Canadian', 'cape verde islands' => 'Cape Verdean', 'cape verde' => 'Cape Verdean',
            'central african republic' => 'Central African', 'chad' => 'Chadian', 'chile' => 'Chilean', 'china' => 'Chinese',
            'colombia' => 'Colombian', 'comoros' => 'Comorian', 'congo' => 'Congolese (Congo-Brazzaville)',
            'congo, the drc' => 'Congolese (Congo-Kinshasa)', 'costa rica' => 'Costa Rican', "cote d'ivoire" => 'Ivorian',
            'croatia' => 'Croatian', 'cuba' => 'Cuban', 'cyprus' => 'Cypriot', 'czech republic' => 'Czech',
            'denmark' => 'Danish', 'djibouti' => 'Djiboutian', 'dominica' => 'Dominican', 'dominican republic' => 'Dominican',
            'east timor' => 'East Timorese', 'ecuador' => 'Ecuadorean', 'egypt' => 'Egyptian',
            'equatorial guinea' => 'Equatorial Guinean', 'eritrea' => 'Eritrean', 'estonia' => 'Estonian',
            'ethiopia' => 'Ethiopian', 'fiji' => 'Fijian', 'finland' => 'Finnish', 'france' => 'French', 'gabon' => 'Gabonese',
            'gambia' => 'Gambian', 'georgia' => 'Georgian', 'germany' => 'German', 'ghana' => 'Ghanaian', 'greece' => 'Greek',
            'grenada' => 'Grenadian', 'guatemala' => 'Guatemalan', 'guinea' => 'Guinean', 'guinea-bissau' => 'Bissau-Guinean',
            'guyana' => 'Guyanese', 'haiti' => 'Haitian', 'honduras' => 'Honduran', 'hungary' => 'Hungarian',
            'iceland' => 'Icelander', 'india' => 'Indian', 'indonesia' => 'Indonesian', 'iran' => 'Iranian', 'iraq' => 'Iraqi',
            'ireland' => 'Irish', 'israel' => 'Israeli', 'italy' => 'Italian', 'jamaica' => 'Jamaican', 'japan' => 'Japanese',
            'jordan' => 'Jordanian', 'kazakhstan' => 'Kazakhstani', 'kenya' => 'Kenyan', 'kiribati' => 'Kiribati',
            'korea, d.p.r.o.' => 'North Korean', 'korea, republic of' => 'South Korean', 'kuwait' => 'Kuwaiti',
            'kyrgyzstan' => 'Kyrgyzstani', 'laos' => 'Lao', 'latvia' => 'Latvian', 'lebanon' => 'Lebanese',
            'liberia' => 'Liberian', 'libyan arab jamahiriya' => 'Libyan', 'libya' => 'Libyan',
            'liechtenstein' => 'Liechtensteiner', 'lithuania' => 'Lithuanian', 'luxembourg' => 'Luxembourger',
            'macedonia' => 'North Macedonian', 'madagascar' => 'Malagasy', 'malawi' => 'Malawian', 'malaysia' => 'Malaysian',
            'maldives' => 'Maldivian', 'mali' => 'Malian', 'malta' => 'Maltese', 'marshall islands' => 'Marshallese',
            'mauritania' => 'Mauritanian', 'mauritius' => 'Mauritian', 'mexico' => 'Mexican', 'micronesia' => 'Micronesian',
            'moldova' => 'Moldovan', 'monaco' => 'Monacan', 'mongolia' => 'Mongolian', 'montenegro' => 'Montenegrin',
            'morocco' => 'Moroccan', 'mozambique' => 'Mozambican', 'myanmar' => 'Burmese', 'namibia' => 'Namibian',
            'nauru' => 'Nauruan', 'nepal' => 'Nepalese', 'netherlands' => 'Dutch', 'new zealand' => 'New Zealander',
            'nicaragua' => 'Nicaraguan', 'niger' => 'Nigerien', 'nigeria' => 'Nigerian', 'norway' => 'Norwegian',
            'oman' => 'Omani', 'pakistan' => 'Pakistani', 'palau' => 'Palauan', 'palestine' => 'Palestinian',
            'panama' => 'Panamanian', 'papua new guinea' => 'Papua New Guinean', 'paraguay' => 'Paraguayan', 'peru' => 'Peruvian',
            'philippines' => 'Filipino', 'poland' => 'Polish', 'portugal' => 'Portuguese', 'qatar' => 'Qatari',
            'romania' => 'Romanian', 'russia' => 'Russian', 'rwanda' => 'Rwandan',
            'saint kitts and nevis' => 'Saint Kitts and Nevisian', 'saint lucia' => 'Saint Lucian',
            'saint vincent and the grenadines' => 'Saint Vincentian', 'samoa' => 'Samoan', 'san marino' => 'San Marinese',
            'sao tome and principe' => 'São Toméan', 'saudi arabia' => 'Saudi Arabian', 'senegal' => 'Senegalese',
            'serbia' => 'Serbian', 'seychelles' => 'Seychellois', 'sierra leone' => 'Sierra Leonean',
            'singapore' => 'Singaporean', 'slovakia' => 'Slovak', 'slovenia' => 'Slovenian', 'solomon islands' => 'Solomon Islander',
            'somalia' => 'Somali', 'south africa' => 'South African', 'south sudan' => 'South Sudanese', 'spain' => 'Spanish',
            'sri lanka' => 'Sri Lankan', 'sudan' => 'Sudanese', 'suriname' => 'Surinamese', 'swaziland' => 'Eswatini',
            'sweden' => 'Swedish', 'switzerland' => 'Swiss', 'syria' => 'Syrian', 'tajikistan' => 'Tajikistani',
            'tanzania' => 'Tanzanian', 'thailand' => 'Thai', 'togo' => 'Togolese', 'tonga' => 'Tongan',
            'trinidad and tobago' => 'Trinidadian and Tobagonian', 'tunisia' => 'Tunisian', 'turkey' => 'Turkish',
            'turkmenistan' => 'Turkmen', 'tuvalu' => 'Tuvaluan', 'uganda' => 'Ugandan', 'ukraine' => 'Ukrainian',
            'united arab emirates' => 'Emirati', 'united kingdom' => 'British', 'united states' => 'American',
            'uruguay' => 'Uruguayan', 'uzbekistan' => 'Uzbekistani', 'vanuatu' => 'Vanuatuan', 'venezuela' => 'Venezuelan',
            'vietnam' => 'Vietnamese', 'yemen' => 'Yemeni', 'zambia' => 'Zambian', 'zimbabwe' => 'Zimbabwean',
        ];
        return $map;
    }

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $map    = $this->countryToDemonym();
        $valid  = collect(config('settings.nationalities'))->mapWithKeys(fn($d) => [strtolower(trim($d)) => $d]);

        $query = DB::table('visa_nationalities');
        if ($this->option('resort')) {
            $query->where('resort_id', (int) $this->option('resort'));
        }
        $rows = $query->get(['id', 'resort_id', 'nationality']);

        $fixed = 0; $deletedDupes = 0; $alreadyOk = 0; $unmapped = [];

        foreach ($rows as $row) {
            $current = trim((string) $row->nationality);
            // Already a valid demonym? leave it.
            if ($valid->has(strtolower($current))) {
                $alreadyOk++;
                continue;
            }
            $demonym = $map[strtolower($current)] ?? null;
            if (!$demonym || !$valid->has(strtolower($demonym))) {
                $unmapped[$current] = true;
                continue;
            }

            // Would this collide with an existing demonym row for the same resort?
            $existing = DB::table('visa_nationalities')
                ->where('resort_id', $row->resort_id)
                ->whereRaw('LOWER(TRIM(nationality)) = ?', [strtolower($demonym)])
                ->where('id', '!=', $row->id)
                ->exists();

            if ($existing) {
                $this->line("  #{$row->id} (resort {$row->resort_id}): \"{$current}\" -> \"{$demonym}\" already exists, DELETE duplicate");
                if (!$dryRun) {
                    DB::table('visa_nationalities')->where('id', $row->id)->delete();
                }
                $deletedDupes++;
            } else {
                $this->line("  #{$row->id} (resort {$row->resort_id}): \"{$current}\" -> \"{$demonym}\"");
                if (!$dryRun) {
                    DB::table('visa_nationalities')->where('id', $row->id)->update(['nationality' => $demonym, 'updated_at' => now()]);
                }
                $fixed++;
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '') . "Done. " . ($dryRun ? 'would fix ' : 'fixed ') . "{$fixed}, " . ($dryRun ? 'would delete ' : 'deleted ') . "{$deletedDupes} duplicate(s), {$alreadyOk} already correct.");
        if (!empty($unmapped)) {
            $this->warn('Unmapped (left unchanged — no matching demonym in config): ' . implode(', ', array_keys($unmapped)));
        }

        return self::SUCCESS;
    }
}
