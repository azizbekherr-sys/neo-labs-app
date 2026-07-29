# NEO-LABS Technical SEO, AEO/GEO auditi

Audit sanasi: 2026-07-29  
Sayt: https://neo-labs.uz  
Tekshirilgan muhitlar: production va lokal Laravel muhiti

## 1. Topilgan asosiy muammolar

### P0 — kritik

- Production’da noma’lum URL `404` o‘rniga `500` qaytarardi. Sabab: custom 404 Blade layout ichida `{locale}` route default o‘rnatilmagan edi.

### P1 — yuqori

- `/sitemap.xml` sitemap index emas, barcha URL’lar bitta `urlset` ichida edi; static indekslanadigan sahifalarning bir qismi sitemapga kirmagan.
- `/` redirecti session/browser holatiga bog‘liq bo‘lib, crawlerlar uchun turli til natijasi berishi mumkin edi. O‘zbekiston auditoriyasi uchun asosiy til deterministik emas edi.
- `x-default` ruscha versiyaga yo‘naltirilgan edi.
- Supabase’dagi tashqi maqola rasmlari OG image va preload’da `neo-labs.uz/storage/...` manziliga noto‘g‘ri o‘zgartirilgan. Production crawl’da shu sababli 2 ta 404 resurs topildi.
- Bir nomli FERFOLAS kapsula va eritma variantlari bir xil title bilan chiqardi.
- About sahifalarining description’i bosh sahifa description’i bilan takrorlangan.
- Kontakt ma’lumotlari yig‘ilsa ham, UZ/RU/EN maxfiylik siyosati yo‘q edi.
- Organization schema’da ish vaqti yo‘q edi.
- Production TTFB namunasi asosiy sahifalarda 0.66–1.21 s, root redirect bilan 1.29 s gacha chiqdi. Render free cold-start va tashqi PostgreSQL birinchi so‘rovni sezilarli sekinlashtiradi.

### P2 — o‘rta

- Til almashtirgich mos til URL’iga to‘g‘ridan-to‘g‘ri emas, `/locale/{locale}` orqali 302 qilardi.
- Ichki sahifalarning barchasida ko‘rinadigan yagona breadcrumb yo‘q edi.
- Home uchun tayyor FAQ tarjimalari mavjud, lekin foydalanuvchiga ko‘rinmas edi.
- Web App Manifest va 192/512 faviconlar yo‘q edi.
- Yandex verification meta uchun konfiguratsiya mavjud emas edi.
- Home, manufacturing, certificates va company-facts ma’lumotlari har so‘rovda tashqi bazadan qayta olinardi.
- Production sitemap auditida 63 canonical URL’ning barchasi 200 qaytardi, lekin yangi static/legal sahifalarni avtomatik qamrash arxitekturasi yetarli emas edi.

### P3 — past yoki monitoring

- `robots.txt` audit sanasi eskirgan edi.
- `llms.txt` mavjud, lekin privacy canonical sahifasi kiritilmagan edi.
- Lokal PHP 8.5 muhitida Laravel 8 vendor paketlaridan deprecation xabarlari chiqadi. Production PHP 8.2’da ishlaydi, ammo framework upgrade rejalashtirilishi kerak.

## 2. Amalga oshirilgan o‘zgarishlar

- 404 handler locale’ni path yoki default `uz` orqali o‘rnatadi; noma’lum URL endi haqiqiy 404 va `noindex, nofollow`.
- `/`, barcha locale’siz legacy sahifalar va legacy product/article URL’lari deterministik 301 bilan `/uz/...` ga yo‘naltirildi.
- Asosiy locale va `x-default` Uzbek versiyaga o‘tkazildi. UZ/RU/EN sahifalari o‘z canonical’iga ega.
- Til menyusi mos localized canonical URL’iga bevosita havola beradi; eski switch route backward compatibility uchun saqlandi.
- Global semantik, ko‘rinadigan breadcrumb qo‘shildi; Product, Article va barcha static ichki sahifalarda ishlaydi. BreadcrumbList JSON-LD bilan mos.
- `/sitemap.xml` sitemap indexga aylantirildi:
  - `/sitemaps/pages-uz.xml`
  - `/sitemaps/pages-ru.xml`
  - `/sitemaps/pages-en.xml`
  - `/sitemaps/products.xml`
  - `/sitemaps/articles.xml`
- Sitemaplar faqat canonical URL’larni, real `updated_at`/view file vaqtini, hreflang va mavjud image URL’larini beradi. Published certificate detail sahifalari ham locale page sitemaplariga qo‘shildi.
- Tashqi Supabase media URL’larini OG, Twitter, preload va sitemapda saqlaydigan alohida asset resolver qo‘shildi.
- Same-name mahsulot variant title’lari real chiqarilish shakli bilan ajratildi: masalan, `FERFOLAS — kapsulalar` va `FERFOLAS — ichish uchun eritma`.
- Home, About va Manufacturing’da ko‘rinadigan, localized FAQ bloklari qo‘shildi; FAQPage faqat aynan ko‘rinadigan savol-javoblardan yaratiladi.
- UZ/RU/EN maxfiylik siyosati, footer linki va sitemap yozuvlari qo‘shildi.
- Organization/LocalBusiness schema’ga Dushanba–Shanba 09:00–18:00 ish vaqti qo‘shildi.
- Yandex verification uchun `YANDEX_SITE_VERIFICATION` environment variabli meta qo‘llab-quvvatlandi. Google va Bing mavjud mexanizmi saqlandi.
- Web App Manifest, 192×192 va 512×512 iconlar qo‘shildi.
- Home va DB-backed asosiy static sahifalar uchun 5 daqiqalik public content cache qo‘shildi. Product, article, partner, certificate yoki company fact saqlansa/o‘chirilsa tegishli cache va sitemap cache avtomatik tozalanadi.
- Cache testi: lokal tashqi DB bilan cold home 10.59 s bo‘lgan, keyingi warm so‘rovlar 12.5 ms va 7.8 ms; manufacturing cold 3.37 s, warm 8.5 ms. Bu o‘lchov Laravel development serveriga tegishli, production CWV o‘rnini bosmaydi.
- `llms.txt`, `llms-full.txt` va robots audit sanasi yangilandi. AI tizimlari uchun yashirin kontent qo‘shilmadi.

## 3. O‘zgartirilgan fayllar

### Backend va konfiguratsiya

- `.env.example`
- `app/Exceptions/Handler.php`
- `app/Http/Controllers/SitemapController.php`
- `app/Providers/AppServiceProvider.php`
- `app/Support/Seo.php`
- `app/Support/PublicContentCache.php`
- `config/app.php`
- `config/seo.php`
- `routes/web.php`

### Blade, localization va UI

- `resources/views/components/layouts/index.blade.php`
- `resources/views/errors/404.blade.php`
- `resources/views/pages/about.blade.php`
- `resources/views/pages/article-show.blade.php`
- `resources/views/pages/manufacturing.blade.php`
- `resources/views/pages/product-show.blade.php`
- `resources/views/pages/privacy.blade.php`
- `resources/views/seo/sitemap.blade.php`
- `resources/views/seo/sitemap-index.blade.php`
- `resources/views/welcome.blade.php`
- `resources/lang/uz/site.php`
- `resources/lang/ru/site.php`
- `resources/lang/en/site.php`
- `public/css/site.css`
- `public/css/content-pages.css`

### Discovery va iconlar

- `public/robots.txt`
- `public/llms.txt`
- `public/llms-full.txt`
- `public/site.webmanifest`
- `public/img/icon-192.png`
- `public/img/icon-512.png`

### Testlar

- `tests/Feature/SeoTest.php`
- `tests/Feature/PublicScopeTest.php`
- `tests/Feature/ExampleTest.php`

## 4. Asosiy URL’lar uchun title va description

| URL | Title | Description |
|---|---|---|
| `/uz` | NEO-LABS — O‘zbekistonda biologik faol qo‘shimchalar ishlab chiqarish | NEO-LABS — O‘zbekistonda biologik faol qo‘shimchalar ishlab chiqaruvchi kompaniya. Tabletka, kapsula, flakon va sache mahsulotlari hamda to‘liq sikldagi kontrakt ishlab chiqarish xizmatlari. |
| `/uz/catalog` | Mahsulotlar katalogi — NEO-LABS | NEO-LABS biologik faol qo‘shimchalari katalogi: shakli, tarkibi va mahsulot haqida ma’lumot. |
| `/uz/manufacturing` | Kontrakt ishlab chiqarish — NEO-LABS | NEO-LABS tabletka, kapsula, flakon va sache formatlari uchun formuladan qadoqlashgacha bo‘lgan kontrakt ishlab chiqarish so‘rovlarini qabul qiladi. |
| `/uz/news` | Maqolalar va yangiliklar — NEO-LABS | NEO-LABS tahririyati tayyorlagan salomatlik va mahsulotlar haqidagi materiallar. |
| `/uz/about` | NEO-LABS kompaniyasi haqida | NEO-LABS tarixi, Toshkentdagi ishlab chiqarish faoliyati, kompaniya ma’lumotlari va tasdiqlovchi hujjatlarga havolalar. |
| `/uz/contacts` | NEO-LABS bilan bog‘lanish | NEO-LABS telefonlari, email, Toshkentdagi joylashuvi va kontrakt ishlab chiqarish uchun aloqa shakli. |
| `/uz/privacy` | Maxfiylik siyosati — NEO-LABS | NEO-LABS saytida aloqa ma’lumotlari, texnik ma’lumotlar va cookie fayllari qanday ishlatilishi haqida ma’lumot. |
| `/ru` | NEO-LABS — производство БАД и контрактное производство в Узбекистане | NEO-LABS — производитель биологически активных добавок в Узбекистане. Производство таблеток, капсул, флаконов и саше, услуги контрактного производства полного цикла. |
| `/ru/catalog` | Каталог продуктов — NEO-LABS | Каталог биологически активных добавок NEO-LABS: форма, состав и информация о продукте. |
| `/ru/manufacturing` | Контрактное производство — NEO-LABS | NEO-LABS принимает запросы на контрактное производство таблеток, капсул, флаконов и саше — от обсуждения формулы до упаковки. |
| `/ru/news` | Статьи и новости — NEO-LABS | Материалы о здоровье и продуктах, подготовленные редакцией NEO-LABS. |
| `/ru/about` | О компании NEO-LABS | История NEO-LABS, производственная деятельность в Ташкенте, сведения о компании и ссылки на подтверждающие документы. |
| `/ru/contacts` | Контакты NEO-LABS | Телефоны, email, местоположение NEO-LABS в Ташкенте и форма запроса на контрактное производство. |
| `/ru/privacy` | Политика конфиденциальности — NEO-LABS | Информация об обработке контактных и технических данных и использовании cookie на сайте NEO-LABS. |
| `/en` | NEO-LABS — dietary supplement and contract manufacturing in Uzbekistan | NEO-LABS is a dietary supplement manufacturer in Uzbekistan, producing tablets, capsules, bottles and sachets and providing full-cycle contract manufacturing services. |
| `/en/catalog` | Product catalogue — NEO-LABS | NEO-LABS dietary supplement catalogue with product form, composition and product information. |
| `/en/manufacturing` | Contract manufacturing — NEO-LABS | NEO-LABS accepts contract manufacturing enquiries for tablets, capsules, bottles and sachets, from formula discussion through to packaging. |
| `/en/news` | Articles and news — NEO-LABS | Health and product information prepared by the NEO-LABS editorial team. |
| `/en/about` | About NEO-LABS | NEO-LABS history, manufacturing activity in Tashkent, company information and links to supporting documents. |
| `/en/contacts` | Contact NEO-LABS | NEO-LABS phone numbers, email, Tashkent location and contact form for contract manufacturing inquiries. |
| `/en/privacy` | Privacy policy — NEO-LABS | Information about contact and technical data processing and cookie use on the NEO-LABS website. |

Product va article title/description’lari DB’dagi localized maydonlardan server-side yaratiladi. Product `seo_override` yoqilsa administrator qiymati ustun turadi; aks holda nom + real shakl title’ni noyob qiladi.

## 5. Qo‘shilgan va tekshirilgan Schema.org turlari

- `Organization`
- `LocalBusiness`
- `WebSite`
- `WebPage`
- `ImageObject`
- `BreadcrumbList`
- `Product`
- `BlogPosting` yoki tekshirilgan article turi
- `FAQPage` — faqat sahifada ko‘rinadigan FAQ bo‘lsa
- `Service` — kontrakt ishlab chiqarish sahifasida
- `CreativeWork` — published certificate detail sahifasida
- `OpeningHoursSpecification`
- `ContactPoint`

Soxta `AggregateRating`, review, narx yoki availability qo‘shilmadi. Real direct-sale narxi bo‘lmasa Product `offers` chiqarilmaydi. `MedicalProduct` ishlatilmadi.

## 6. Test va tekshiruv natijalari

- Production status/TTFB sample: 12 endpoint tekshirildi; indekslanadigan asosiy sahifalar 200. Auditdan oldin noma’lum URL 500 edi.
- Production sitemap crawl: 63/63 URL 200, canonical va H1 tekshiruvi muvaffaqiyatli.
- Production internal link crawl: 117 unique internal URL; 2 ta noto‘g‘ri rewritten external media preload/OG URL topildi va kodda tuzatildi.
- Lokal static SEO matrix: 33/33 UZ/RU/EN asosiy URL 200; canonical to‘g‘ri; 1 ta H1; JSON-LD valid; duplicate title/description yo‘q.
- Sitemap index va 5 child sitemap `xmllint` bilan valid. Query, localhost yoki 127.0.0.1 URL topilmadi.
- Targeted feature tests: 23/23 pass, jumladan public cache invalidation testi.
- PHP/Blade lint: o‘zgartirilgan PHP va Blade fayllarida syntax xato yo‘q.
- Blade view cache: muvaffaqiyatli.
- Browser desktop QA: bosh sahifa, SSR navigatsiya, FAQ va footer ko‘rindi.
- Browser mobile QA (390×844): gorizontal overflow yo‘q (`scrollWidth = innerWidth = 390`), menu `aria-expanded` ishlaydi, privacy sahifasida 1 H1, breadcrumb, canonical, manifest va `lang=uz` mavjud.
- Full suite: SEO’ga tegishli o‘zgarishlar pass. Oldindan mavjud 3 unrelated test qoladi: admin modal markup bo‘yicha 2 ta stale expectation va test muhitidagi Supabase certificate storage assertion.

## 7. Sayt egasi qo‘lda bajarishi kerak bo‘lgan qadamlar

### Google Search Console

1. `neo-labs.uz` Domain property yarating va Search Console bergan TXT/CNAME qiymatini DNS’ga aynan o‘sha ko‘rinishda qo‘shing. Tokenni o‘ylab topmang.
2. Muqobil URL-prefix verification kerak bo‘lsa, Search Console bergan tokenni Render environment’da `GOOGLE_SITE_VERIFICATION` ga kiriting va deploy qiling.
3. Sitemaps bo‘limida `https://neo-labs.uz/sitemap.xml` ni yuboring.
4. URL Inspection orqali `/uz`, `/ru`, `/en`, uchala catalog, manufacturing, news, about va contacts URL’larini live-test qiling; deploydan keyin muhim URL’lar uchun indexing so‘rang.
5. Pages, Sitemaps, Core Web Vitals va Enhancements hisobotlarini 2–4 hafta kuzating.

Rasmiy qo‘llanmalar:

- https://support.google.com/webmasters/answer/9008080
- https://support.google.com/webmasters/answer/7451001
- https://support.google.com/webmasters/answer/9012289

### Bing Webmaster Tools

1. Google Search Console property’ni import qiling yoki `neo-labs.uz` ni qo‘lda qo‘shing.
2. Bing bergan meta tokenni `BING_SITE_VERIFICATION` ga kiriting yoki Bing ko‘rsatgan DNS usulidan foydalaning.
3. `https://neo-labs.uz/sitemap.xml` ni yuboring va Site Explorer/URL Inspection’da xatolarni tekshiring.

Rasmiy qo‘llanmalar:

- https://www.bing.com/webmasters/help/add-and-verify-site-12184f8b
- https://www.bing.com/webmasters/help/Sitemaps-3b5cf6ed

### Yandex Webmaster

1. `https://neo-labs.uz` ni qo‘shing.
2. Yandex bergan meta tokenni Render’da `YANDEX_SITE_VERIFICATION` ga kiriting yoki DNS TXT usulini tanlang.
3. `https://neo-labs.uz/sitemap.xml` ni Sitemap files bo‘limiga yuboring va Yandex validatorida tekshiring.
4. Region sifatida O‘zbekiston/Toshkent biznes ma’lumotlarini faqat real tasdiqlangan manzil bilan kiriting.

Rasmiy qo‘llanmalar:

- https://yandex.com/support/webmaster/en/service/rights
- https://yandex.com/support/webmaster/en/indexing-options/sitemap

### Local business

- Real biznes mavjud va profilga mos bo‘lsa Google Business Profile’ni claim/verify qiling; sayt, nom, telefon va tasdiqlangan manzilni saytdagi qiymatlar bilan bir xil saqlang.
- Bing Places va mahalliy kataloglarda ham aynan bir xil NAP ma’lumotlarini ishlating.
- Google Business Profile rasmiy yo‘riqnomasi: https://support.google.com/business/answer/2911778

### IndexNow bahosi

Hozir majburiy emas: sayt hajmi kichik, XML sitemap avtomatik va Bing/Yandex discovery mavjud. Mahsulot/maqolalar tez-tez yangilana boshlasa, admin save/delete hodisalaridan keyin IndexNow notification qo‘shish foydali. Bunda real IndexNow key yaratilishi va root’da key file joylashtirilishi kerak; tokenni kodga o‘ylab yozmaslik lozim.

Rasmiy hujjat: https://www.indexnow.org/documentation

## 8. Qolgan xavflar va tavsiyalar

- **P1:** Kod deploy qilinmaguncha production’da eski 500 404, monolit sitemap va media rewrite muammolari qoladi.
- **P1:** Render free plan cold-startni kod to‘liq yo‘q qila olmaydi. Barqaror crawl/TTFB uchun always-on Starter yoki undan yuqori plan va production APM tavsiya qilinadi.
- **P1:** Mahsulot matnlaridagi “oldini oladi”, kasallik, homiladorlik yoki fiziologik natija haqidagi mavjud da’volarni mas’ul tibbiy/regulatory mutaxassis hujjatlar asosida ko‘rib chiqishi kerak. Audit yangi davolash da’vosi qo‘shmadi.
- **P1:** Exact ko‘cha/bino manzili tasdiqlanmagan. Organization schema ataylab faqat Toshkent/Sergeli darajasida. Tasdiqlanmaguncha soxta manzil qo‘shmang.
- **P2:** “2025-yil uchun tibbiyotdagi yangi yangiliklar” maqolasi 2026-yilda eskirgan ko‘rinadi; faktlar va manbalarni yangilang yoki arxiv maqolasi sifatida qayta nomlang.
- **P2:** Real muallif, reviewer va manbasi yo‘q tibbiy maqolalarni ekspert tekshiruvisiz `reviewedBy` bilan belgilamang.
- **P2:** Search Console, Bing va Yandex tokenlari faqat platformalar bergan real qiymatlar bilan Render environment’da to‘ldirilishi kerak.
- **P2:** 28 kunlik real-user Core Web Vitals ma’lumoti faqat Search Console/CrUX’dan olinadi. Lokal TTFB va browser QA laboratoriya tekshiruvi, CWV kafolati emas.
- **P3:** Laravel 8 va eski Collision/PHPUnit paketlari PHP 8.5’da deprecation chiqaradi. Alohida dependency-upgrade sprint rejalashtiring.
- Sitelinks Google tomonidan avtomatik tanlanadi. Yangi SSR header/footer linklari, breadcrumb va unique metadata ehtimolni oshiradi, ammo kafolat bermaydi.
