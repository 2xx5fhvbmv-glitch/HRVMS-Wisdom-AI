<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalizes `employees.nationality` values so they match the demonyms
 * expected by the UI dropdown (config/settings.php 'nationalities').
 * The Excel import stored country names like "Indonesia" or "Sri Lanka";
 * this migration remaps them to "Indonesian" / "Sri Lankan" etc.
 *
 * Only updates rows that currently hold a country name from the map;
 * rows already using a valid demonym are left alone.
 */
class NormalizeEmployeeNationalities extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('employees') || !Schema::hasColumn('employees', 'nationality')) {
            return;
        }

        $map = self::countryToDemonymMap();
        $updated = 0;
        foreach ($map as $country => $demonym) {
            $affected = DB::table('employees')
                ->where('nationality', $country)
                ->update(['nationality' => $demonym, 'updated_at' => now()]);
            $updated += $affected;
        }
        // Safe no-op if no rows match
    }

    public function down()
    {
        // Irreversible — we don't track which rows were previously country-named.
    }

    /**
     * Country name (as stored in the Excel import) → UI dropdown demonym.
     * Shared with ImportWisdomAiStaffSeeder for future imports.
     */
    public static function countryToDemonymMap(): array
    {
        return [
            'Maldives'          => 'Maldivian',
            'Bangladesh'        => 'Bangladeshi',
            'India'             => 'Indian',
            'Sri Lanka'         => 'Sri Lankan',
            'Indonesia'         => 'Indonesian',
            'Nepal'             => 'Nepalese',
            'China'             => 'Chinese',
            'Philippines'       => 'Filipino',
            'Morocco'           => 'Moroccan',
            'Bhutan'            => 'Bhutanese',
            'Zimbabwe'          => 'Zimbabwean',
            'Korea (south)'     => 'South Korean',
            'Korea (South)'     => 'South Korean',
            'South Korea'       => 'South Korean',
            'Korea (north)'     => 'North Korean',
            'North Korea'       => 'North Korean',
            'Egypt'             => 'Egyptian',
            'South Africa'      => 'South African',
            'Russia'            => 'Russian',
            'Myanmar'           => 'Burmese',
            'Kazakhstan'        => 'Kazakhstani',
            'Kyrgyzstan'        => 'Kyrgyzstani',
            'Pakistan'          => 'Pakistani',
            'Afghanistan'       => 'Afghan',
            'Albania'           => 'Albanian',
            'Algeria'           => 'Algerian',
            'Andorra'           => 'Andorran',
            'Angola'            => 'Angolan',
            'Argentina'         => 'Argentinean',
            'Armenia'           => 'Armenian',
            'Australia'         => 'Australian',
            'Austria'           => 'Austrian',
            'Azerbaijan'        => 'Azerbaijani',
            'Belarus'           => 'Belarusian',
            'Belgium'           => 'Belgian',
            'Bolivia'           => 'Bolivian',
            'Brazil'            => 'Brazilian',
            'Bulgaria'          => 'Bulgarian',
            'Cambodia'          => 'Cambodian',
            'Canada'            => 'Canadian',
            'Chile'             => 'Chilean',
            'Colombia'          => 'Colombian',
            'Croatia'           => 'Croatian',
            'Cuba'              => 'Cuban',
            'Cyprus'            => 'Cypriot',
            'Czech Republic'    => 'Czech',
            'Denmark'           => 'Danish',
            'Ecuador'           => 'Ecuadorian',
            'Estonia'           => 'Estonian',
            'Ethiopia'          => 'Ethiopian',
            'Finland'           => 'Finnish',
            'France'            => 'French',
            'Georgia'           => 'Georgian',
            'Germany'           => 'German',
            'Ghana'             => 'Ghanaian',
            'Greece'            => 'Greek',
            'Hungary'           => 'Hungarian',
            'Iceland'           => 'Icelandic',
            'Iran'              => 'Iranian',
            'Iraq'              => 'Iraqi',
            'Ireland'           => 'Irish',
            'Israel'            => 'Israeli',
            'Italy'             => 'Italian',
            'Japan'             => 'Japanese',
            'Jordan'            => 'Jordanian',
            'Kenya'             => 'Kenyan',
            'Kuwait'            => 'Kuwaiti',
            'Laos'              => 'Laotian',
            'Latvia'            => 'Latvian',
            'Lebanon'           => 'Lebanese',
            'Libya'             => 'Libyan',
            'Lithuania'         => 'Lithuanian',
            'Luxembourg'        => 'Luxembourgish',
            'Malaysia'          => 'Malaysian',
            'Mauritius'         => 'Mauritian',
            'Mexico'            => 'Mexican',
            'Mongolia'          => 'Mongolian',
            'Netherlands'       => 'Dutch',
            'New Zealand'       => 'New Zealander',
            'Nigeria'           => 'Nigerian',
            'Norway'            => 'Norwegian',
            'Oman'              => 'Omani',
            'Peru'              => 'Peruvian',
            'Poland'            => 'Polish',
            'Portugal'          => 'Portuguese',
            'Qatar'             => 'Qatari',
            'Romania'           => 'Romanian',
            'Saudi Arabia'      => 'Saudi Arabian',
            'Senegal'           => 'Senegalese',
            'Serbia'            => 'Serbian',
            'Singapore'         => 'Singaporean',
            'Slovakia'          => 'Slovak',
            'Slovenia'          => 'Slovene',
            'Somalia'           => 'Somali',
            'Spain'             => 'Spanish',
            'Sudan'             => 'Sudanese',
            'Sweden'            => 'Swedish',
            'Switzerland'       => 'Swiss',
            'Syria'             => 'Syrian',
            'Taiwan'            => 'Taiwanese',
            'Tajikistan'        => 'Tajik',
            'Tanzania'          => 'Tanzanian',
            'Thailand'          => 'Thai',
            'Tunisia'           => 'Tunisian',
            'Turkey'            => 'Turkish',
            'Uganda'            => 'Ugandan',
            'Ukraine'           => 'Ukrainian',
            'United Arab Emirates' => 'Emirati',
            'UAE'               => 'Emirati',
            'United Kingdom'    => 'British',
            'UK'                => 'British',
            'United States'     => 'American',
            'USA'               => 'American',
            'Uruguay'           => 'Uruguayan',
            'Uzbekistan'        => 'Uzbekistani',
            'Venezuela'         => 'Venezuelan',
            'Vietnam'           => 'Vietnamese',
            'Yemen'             => 'Yemenite',
            'Zambia'            => 'Zambian',
        ];
    }
}
