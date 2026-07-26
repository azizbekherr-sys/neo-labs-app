<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Partner;
use App\Models\Article;

/**
 * Sample/demo content for local development. Populates the Supabase
 * database with a few products, partners and articles that reuse the
 * real image files already present under public/. Idempotent: safe to
 * re-run — rows are matched by their Russian name/title.
 *
 * Run with:  php artisan db:seed --class=Database\\Seeders\\SampleDataSeeder --force
 */
class SampleDataSeeder extends Seeder
{
    public function run()
    {
        $products = [
            [
                'name_ru' => 'Витамин C 1000',
                'name_uz' => 'Vitamin C 1000',
                'type_ru' => 'Витамины',
                'type_uz' => 'Vitaminlar',
                'composition_ru' => 'Аскорбиновая кислота 1000 мг',
                'composition_uz' => 'Askorbin kislotasi 1000 mg',
                'description_ru' => 'Поддержка иммунитета и антиоксидантная защита.',
                'description_uz' => 'Immunitetni qo‘llab-quvvatlash va antioksidant himoya.',
                'price' => 79000,
                'image' => 'products/p_691652d6885f1_1763070678.png',
            ],
            [
                'name_ru' => 'Омега-3',
                'name_uz' => 'Omega-3',
                'type_ru' => 'Жирные кислоты',
                'type_uz' => 'Yog‘ kislotalari',
                'composition_ru' => 'Рыбий жир, EPA/DHA',
                'composition_uz' => 'Baliq yog‘i, EPA/DHA',
                'description_ru' => 'Для сердца, сосудов и работы мозга.',
                'description_uz' => 'Yurak, qon tomirlar va miya faoliyati uchun.',
                'price' => 129000,
                'image' => 'products/p_691652d68891b_1763070678.png',
            ],
            [
                'name_ru' => 'Магний B6',
                'name_uz' => 'Magniy B6',
                'type_ru' => 'Минералы',
                'type_uz' => 'Minerallar',
                'composition_ru' => 'Магний, витамин B6',
                'composition_uz' => 'Magniy, vitamin B6',
                'description_ru' => 'Снижение усталости и поддержка нервной системы.',
                'description_uz' => 'Charchoqni kamaytirish va asab tizimini qo‘llab-quvvatlash.',
                'price' => 95000,
                'image' => 'products/p_69165496903af_1763071126.png',
            ],
            [
                'name_ru' => 'Цинк + Селен',
                'name_uz' => 'Rux + Selen',
                'type_ru' => 'Минералы',
                'type_uz' => 'Minerallar',
                'composition_ru' => 'Цинк 15 мг, селен 55 мкг',
                'composition_uz' => 'Rux 15 mg, selen 55 mkg',
                'description_ru' => 'Поддержка иммунитета, кожи и волос.',
                'description_uz' => 'Immunitet, teri va soch uchun qo‘llab-quvvatlash.',
                'price' => 88000,
                'image' => 'products/p_69165496906ab_1763071126.png',
            ],
            [
                'name_ru' => 'Витамин D3',
                'name_uz' => 'Vitamin D3',
                'type_ru' => 'Витамины',
                'type_uz' => 'Vitaminlar',
                'composition_ru' => 'Холекальциферол 2000 МЕ',
                'composition_uz' => 'Xolekalsiferol 2000 XB',
                'description_ru' => 'Для костей, зубов и иммунитета.',
                'description_uz' => 'Suyak, tish va immunitet uchun.',
                'price' => 72000,
                'image' => 'products/p_69165496908b1_1763071126.png',
            ],
            [
                'name_ru' => 'Пробиотик Комплекс',
                'name_uz' => 'Probiotik Kompleks',
                'type_ru' => 'Пробиотики',
                'type_uz' => 'Probiotiklar',
                'composition_ru' => '10 штаммов, 5 млрд КОЕ',
                'composition_uz' => '10 shtamm, 5 mlrd KOE',
                'description_ru' => 'Здоровая микрофлора и пищеварение.',
                'description_uz' => 'Sog‘lom mikroflora va hazm qilish.',
                'price' => 145000,
                'image' => 'products/p_691654a3a7ea2_1763071139.png',
            ],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['name_ru' => $p['name_ru']],
                array_merge($p, [
                    'name'    => $p['name_ru'],
                    'type'    => $p['type_ru'],
                    'status'  => 'active',
                    'stock'   => 100,
                    'images'  => [$p['image']],
                ])
            );
        }

        $partners = [
            'partners/pr_691a45506cd59_1763329360.png',
            'partners/pr_691f726c2fbea_1763668588.png',
            'partners/pr_692332c9bfb0e_1763914441.PNG',
            'partners/pr_692427830a71e_1763977091.png',
        ];
        foreach ($partners as $path) {
            Partner::updateOrCreate(['path' => $path], ['url' => null]);
        }

        $articles = [
            [
                'title_ru' => 'Как выбрать витамины осенью',
                'title_uz' => 'Kuzda vitaminlarni qanday tanlash kerak',
                'description_ru' => 'Практические советы по подбору добавок в холодный сезон.',
                'description_uz' => 'Sovuq mavsumda qo‘shimchalarni tanlash bo‘yicha amaliy maslahatlar.',
                'photo' => 'articles/a_692203937793b_1763836819.webp',
                'views' => 42,
            ],
            [
                'title_ru' => 'Контрактное производство: как это работает',
                'title_uz' => 'Kontrakt ishlab chiqarish: bu qanday ishlaydi',
                'description_ru' => 'Этапы производства БАД от идеи до готового продукта.',
                'description_uz' => 'BAD ishlab chiqarishning g‘oyadan tayyor mahsulotgacha bosqichlari.',
                'photo' => 'articles/a_692334d580fb9_1763914965.jpg',
                'views' => 27,
            ],
            [
                'title_ru' => 'Омега-3: польза для здоровья',
                'title_uz' => 'Omega-3: salomatlik uchun foydasi',
                'description_ru' => 'Разбираемся, зачем нужны жирные кислоты и как их принимать.',
                'description_uz' => 'Yog‘ kislotalari nima uchun kerakligini va qanday qabul qilishni ko‘rib chiqamiz.',
                'photo' => 'articles/a_6923352a2eb93_1763915050.jpg',
                'views' => 63,
            ],
        ];
        foreach ($articles as $a) {
            Article::updateOrCreate(['title_ru' => $a['title_ru']], $a);
        }
    }
}
