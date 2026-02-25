<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    public function run()
    {
        $countries = [
            ['name' => 'Afghanistan', 'shortname' => 'AF', 'phonecode' => 93],
            ['name' => 'Albania', 'shortname' => 'AL', 'phonecode' => 355],
            ['name' => 'Algeria', 'shortname' => 'DZ', 'phonecode' => 213],
            ['name' => 'Argentina', 'shortname' => 'AR', 'phonecode' => 54],
            ['name' => 'Australia', 'shortname' => 'AU', 'phonecode' => 61],
            ['name' => 'Austria', 'shortname' => 'AT', 'phonecode' => 43],
            ['name' => 'Bahrain', 'shortname' => 'BH', 'phonecode' => 973],
            ['name' => 'Bangladesh', 'shortname' => 'BD', 'phonecode' => 880],
            ['name' => 'Belgium', 'shortname' => 'BE', 'phonecode' => 32],
            ['name' => 'Brazil', 'shortname' => 'BR', 'phonecode' => 55],
            ['name' => 'Canada', 'shortname' => 'CA', 'phonecode' => 1],
            ['name' => 'China', 'shortname' => 'CN', 'phonecode' => 86],
            ['name' => 'Colombia', 'shortname' => 'CO', 'phonecode' => 57],
            ['name' => 'Egypt', 'shortname' => 'EG', 'phonecode' => 20],
            ['name' => 'Ethiopia', 'shortname' => 'ET', 'phonecode' => 251],
            ['name' => 'France', 'shortname' => 'FR', 'phonecode' => 33],
            ['name' => 'Germany', 'shortname' => 'DE', 'phonecode' => 49],
            ['name' => 'Ghana', 'shortname' => 'GH', 'phonecode' => 233],
            ['name' => 'India', 'shortname' => 'IN', 'phonecode' => 91],
            ['name' => 'Indonesia', 'shortname' => 'ID', 'phonecode' => 62],
            ['name' => 'Iran', 'shortname' => 'IR', 'phonecode' => 98],
            ['name' => 'Iraq', 'shortname' => 'IQ', 'phonecode' => 964],
            ['name' => 'Ireland', 'shortname' => 'IE', 'phonecode' => 353],
            ['name' => 'Italy', 'shortname' => 'IT', 'phonecode' => 39],
            ['name' => 'Japan', 'shortname' => 'JP', 'phonecode' => 81],
            ['name' => 'Jordan', 'shortname' => 'JO', 'phonecode' => 962],
            ['name' => 'Kenya', 'shortname' => 'KE', 'phonecode' => 254],
            ['name' => 'Kuwait', 'shortname' => 'KW', 'phonecode' => 965],
            ['name' => 'Lebanon', 'shortname' => 'LB', 'phonecode' => 961],
            ['name' => 'Malaysia', 'shortname' => 'MY', 'phonecode' => 60],
            ['name' => 'Maldives', 'shortname' => 'MV', 'phonecode' => 960],
            ['name' => 'Mexico', 'shortname' => 'MX', 'phonecode' => 52],
            ['name' => 'Morocco', 'shortname' => 'MA', 'phonecode' => 212],
            ['name' => 'Nepal', 'shortname' => 'NP', 'phonecode' => 977],
            ['name' => 'Netherlands', 'shortname' => 'NL', 'phonecode' => 31],
            ['name' => 'New Zealand', 'shortname' => 'NZ', 'phonecode' => 64],
            ['name' => 'Nigeria', 'shortname' => 'NG', 'phonecode' => 234],
            ['name' => 'Oman', 'shortname' => 'OM', 'phonecode' => 968],
            ['name' => 'Pakistan', 'shortname' => 'PK', 'phonecode' => 92],
            ['name' => 'Philippines', 'shortname' => 'PH', 'phonecode' => 63],
            ['name' => 'Poland', 'shortname' => 'PL', 'phonecode' => 48],
            ['name' => 'Portugal', 'shortname' => 'PT', 'phonecode' => 351],
            ['name' => 'Qatar', 'shortname' => 'QA', 'phonecode' => 974],
            ['name' => 'Russia', 'shortname' => 'RU', 'phonecode' => 7],
            ['name' => 'Saudi Arabia', 'shortname' => 'SA', 'phonecode' => 966],
            ['name' => 'Singapore', 'shortname' => 'SG', 'phonecode' => 65],
            ['name' => 'South Africa', 'shortname' => 'ZA', 'phonecode' => 27],
            ['name' => 'South Korea', 'shortname' => 'KR', 'phonecode' => 82],
            ['name' => 'Spain', 'shortname' => 'ES', 'phonecode' => 34],
            ['name' => 'Sri Lanka', 'shortname' => 'LK', 'phonecode' => 94],
            ['name' => 'Sweden', 'shortname' => 'SE', 'phonecode' => 46],
            ['name' => 'Switzerland', 'shortname' => 'CH', 'phonecode' => 41],
            ['name' => 'Thailand', 'shortname' => 'TH', 'phonecode' => 66],
            ['name' => 'Turkey', 'shortname' => 'TR', 'phonecode' => 90],
            ['name' => 'United Arab Emirates', 'shortname' => 'AE', 'phonecode' => 971],
            ['name' => 'United Kingdom', 'shortname' => 'GB', 'phonecode' => 44],
            ['name' => 'United States', 'shortname' => 'US', 'phonecode' => 1],
            ['name' => 'Vietnam', 'shortname' => 'VN', 'phonecode' => 84],
        ];

        foreach ($countries as $country) {
            DB::table('countries')->updateOrInsert(
                ['shortname' => $country['shortname']],
                $country
            );
        }
    }
}
