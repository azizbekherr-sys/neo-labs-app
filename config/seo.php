<?php

return [
    /* Public URLs must never depend on a local/test APP_URL. */
    'base_url' => env('SEO_BASE_URL', 'https://neo-labs.uz'),
    'canonical_host' => env('SEO_CANONICAL_HOST', 'neo-labs.uz'),
    'force_canonical_host' => env('SEO_FORCE_CANONICAL_HOST', true),
    'locales' => ['ru', 'uz', 'en'],
    'default_locale' => 'uz',

    'site_name' => 'NEO-LABS',
    'legal_name' => env('COMPANY_LEGAL_NAME', 'ООО «NEO-LABS»'),
    'alternate_name' => 'NEO LABS',
    'founding_date' => '2020-06-25',
    'phone' => env('COMPANY_PHONE', '+998991018839'),
    'secondary_phone' => env('COMPANY_SECONDARY_PHONE', '+998974459639'),
    'email' => env('COMPANY_EMAIL', 'neo_labs2019@mail.ru'),
    'tax_id' => env('COMPANY_TAX_ID'),
    'employee_count' => env('COMPANY_EMPLOYEE_COUNT'),
    'opening_hours' => [
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'opens' => '09:00',
        'closes' => '18:00',
    ],

    'address' => [
        'country_code' => 'UZ',
        'country' => ['ru' => 'Узбекистан', 'uz' => 'O‘zbekiston', 'en' => 'Uzbekistan'],
        'locality' => ['ru' => 'Ташкент', 'uz' => 'Toshkent', 'en' => 'Tashkent'],
        'district' => ['ru' => 'Сергелийский район', 'uz' => 'Sergeli tumani', 'en' => 'Sergeli district'],
        // Set only after the exact street/building has been confirmed.
        'street' => env('COMPANY_STREET_ADDRESS'),
        'postal_code' => env('COMPANY_POSTAL_CODE'),
    ],

    'descriptions' => [
        'ru' => 'NEO-LABS — производитель биологически активных добавок в Узбекистане. Производство таблеток, капсул, флаконов и саше, услуги контрактного производства полного цикла.',
        'uz' => 'NEO-LABS — O‘zbekistonda biologik faol qo‘shimchalar ishlab chiqaruvchi kompaniya. Tabletka, kapsula, flakon va sache mahsulotlari hamda to‘liq sikldagi kontrakt ishlab chiqarish xizmatlari.',
        'en' => 'NEO-LABS is a dietary supplement manufacturer in Uzbekistan, producing tablets, capsules, bottles and sachets and providing full-cycle contract manufacturing services.',
    ],
    'home_titles' => [
        'ru' => 'NEO-LABS — производство БАД и контрактное производство в Узбекистане',
        'uz' => 'NEO-LABS — O‘zbekistonda biologik faol qo‘shimchalar ishlab chiqarish',
        'en' => 'NEO-LABS — dietary supplement and contract manufacturing in Uzbekistan',
    ],

    'logo' => '/img/logo.png',
    'default_image' => '/img/neo-labs-og.jpg',
    'social_profiles' => array_values(array_filter([
        env('COMPANY_INSTAGRAM_URL'),
        env('COMPANY_FACEBOOK_URL'),
        env('COMPANY_LINKEDIN_URL'),
        env('COMPANY_YOUTUBE_URL'),
        env('COMPANY_TELEGRAM_URL'),
        env('COMPANY_GOLDEN_PAGES_URL'),
        env('COMPANY_GOOGLE_BUSINESS_URL'),
    ])),

    'verification' => [
        'google' => env('GOOGLE_SITE_VERIFICATION'),
        'bing' => env('BING_SITE_VERIFICATION'),
        'yandex' => env('YANDEX_SITE_VERIFICATION'),
    ],

    'analytics' => [
        'ga_id' => env('GOOGLE_ANALYTICS_ID'),
    ],
];
