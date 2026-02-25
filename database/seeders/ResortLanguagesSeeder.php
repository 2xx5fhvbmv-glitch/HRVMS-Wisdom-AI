<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResortLanguagesSeeder extends Seeder
{
    public function run()
    {
        $languages = [
            ['name' => 'English', 'sort_name' => 'en', 'native' => 'English', 'country_code' => 'GB'],
            ['name' => 'Arabic', 'sort_name' => 'ar', 'native' => 'العربية', 'country_code' => 'SA'],
            ['name' => 'Hindi', 'sort_name' => 'hi', 'native' => 'हिन्दी', 'country_code' => 'IN'],
            ['name' => 'Urdu', 'sort_name' => 'ur', 'native' => 'اردو', 'country_code' => 'PK'],
            ['name' => 'French', 'sort_name' => 'fr', 'native' => 'Français', 'country_code' => 'FR'],
            ['name' => 'Spanish', 'sort_name' => 'es', 'native' => 'Español', 'country_code' => 'ES'],
            ['name' => 'German', 'sort_name' => 'de', 'native' => 'Deutsch', 'country_code' => 'DE'],
            ['name' => 'Chinese', 'sort_name' => 'zh', 'native' => '中文', 'country_code' => 'CN'],
            ['name' => 'Japanese', 'sort_name' => 'ja', 'native' => '日本語', 'country_code' => 'JP'],
            ['name' => 'Korean', 'sort_name' => 'ko', 'native' => '한국어', 'country_code' => 'KR'],
            ['name' => 'Portuguese', 'sort_name' => 'pt', 'native' => 'Português', 'country_code' => 'PT'],
            ['name' => 'Russian', 'sort_name' => 'ru', 'native' => 'Русский', 'country_code' => 'RU'],
            ['name' => 'Italian', 'sort_name' => 'it', 'native' => 'Italiano', 'country_code' => 'IT'],
            ['name' => 'Turkish', 'sort_name' => 'tr', 'native' => 'Türkçe', 'country_code' => 'TR'],
            ['name' => 'Tagalog', 'sort_name' => 'tl', 'native' => 'Tagalog', 'country_code' => 'PH'],
            ['name' => 'Malay', 'sort_name' => 'ms', 'native' => 'Bahasa Melayu', 'country_code' => 'MY'],
            ['name' => 'Thai', 'sort_name' => 'th', 'native' => 'ไทย', 'country_code' => 'TH'],
            ['name' => 'Bengali', 'sort_name' => 'bn', 'native' => 'বাংলা', 'country_code' => 'BD'],
            ['name' => 'Tamil', 'sort_name' => 'ta', 'native' => 'தமிழ்', 'country_code' => 'IN'],
            ['name' => 'Nepali', 'sort_name' => 'ne', 'native' => 'नेपाली', 'country_code' => 'NP'],
            ['name' => 'Sinhala', 'sort_name' => 'si', 'native' => 'සිංහල', 'country_code' => 'LK'],
            ['name' => 'Swahili', 'sort_name' => 'sw', 'native' => 'Kiswahili', 'country_code' => 'KE'],
            ['name' => 'Dutch', 'sort_name' => 'nl', 'native' => 'Nederlands', 'country_code' => 'NL'],
            ['name' => 'Polish', 'sort_name' => 'pl', 'native' => 'Polski', 'country_code' => 'PL'],
            ['name' => 'Persian', 'sort_name' => 'fa', 'native' => 'فارسی', 'country_code' => 'IR'],
            ['name' => 'Vietnamese', 'sort_name' => 'vi', 'native' => 'Tiếng Việt', 'country_code' => 'VN'],
            ['name' => 'Indonesian', 'sort_name' => 'id', 'native' => 'Bahasa Indonesia', 'country_code' => 'ID'],
            ['name' => 'Amharic', 'sort_name' => 'am', 'native' => 'አማርኛ', 'country_code' => 'ET'],
        ];

        foreach ($languages as $language) {
            DB::table('resort_languages')->updateOrInsert(
                ['sort_name' => $language['sort_name']],
                $language
            );
        }
    }
}
