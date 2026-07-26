# NEO-LABS public site audit and release notes

Audit date: 2026-07-20  
Scope: 60 canonical pages across UZ, RU and EN.

## Stack and source of truth

- Laravel 8.83 / PHP / Blade, Eloquent and Laravel Mix 6.
- Products and articles use one database row and ID with `_uz`, `_ru`, `_en` fields.
- Public routes are locale-prefixed in `routes/web.php`; legacy routes redirect to the preferred locale.
- Product and article content is managed through the authenticated Laravel dashboard.
- No SPA migration or new frontend framework was introduced.
- The directory is not a Git worktree, so Git dirty-state comparison was unavailable.

## Scope and SEO

- 18 localized static/index pages: home, catalog, manufacturing, news, about and contacts.
- 27 localized product detail pages: 9 product IDs × 3 locales.
- 12 localized article detail pages: 4 article IDs × 3 locales.
- 3 localized `neo-labs-editorial-team` author pages.
- `sitemap.xml` is deliberately limited to these 60 canonical URLs.
- Every tested scope page has one H1, a clean canonical, UZ/RU/EN reciprocal alternates and `x-default` pointing to RU.
- Search result pages use `noindex, follow`; query strings are excluded from canonical URLs.

## Data migration

Migration `2026_07_20_000004_normalize_product_content.php` adds:

- localized short descriptions, product form and disclaimer;
- external purchase URL and instruction PDF path;
- `complete` / `incomplete` content status;
- `medical_review_required` and `is_featured` flags.

Existing descriptions are converted to 140–220-character plain-text excerpts without changing the source full description. The migration distinguishes FERFOLAS oral solution (ID 4) and FERFOLAS capsules (ID 5) in all three languages. IMULOR (ID 7) is marked `incomplete`. Six complete products are selected as featured.

## Medical-review-required inventory

The following is a keyword-assisted editorial inventory, not a medical judgment. The original UZ/RU/EN claims were not rewritten or expanded. All nine records remain flagged for qualified human and regulatory review:

1. **Neo Magniy B6 (ID 1):** effectiveness, calming the nervous system, stress/fatigue and related benefit language.
2. **Sink+ (ID 2):** immunity activation, viral/bacterial resistance, wound healing, inflammation and pregnancy-related language.
3. **Neo Kalsiy D3 (ID 3):** osteoporosis prevention, pain and pregnancy-related language.
4. **FERFOLAS — oral solution (ID 4):** iron deficiency/anemia prevention, pregnancy/lactation and neural-tube-development language.
5. **FERFOLAS — capsules (ID 5):** iron deficiency/anemia prevention, pregnancy/lactation and neural-tube-development language.
6. **MAXLER VIT (ID 6):** pregnancy, breastfeeding, fetal development and immunity claims.
7. **IMULOR (ID 7):** reduction of throat pain and irritation.
8. **Magniy Forte B6 (ID 8):** irritability, spasms/cramps, sleep and cardiovascular benefit language.
9. **PIKAMIN (ID 9):** recommendations for stress, appetite, weakness and recovery.

The public UI displays the approved project disclaimer consistently:

- UZ: “Biologik faol qo‘shimcha. Dori vositasi emas.”
- RU: “Биологически активная добавка. Не является лекарственным средством.”
- EN: “Dietary supplement. Not a medicine.”

## Incomplete data and translations

- Product names and types exist for all 9 records in UZ/RU/EN.
- Article titles and bodies exist for all 4 records in UZ/RU/EN.
- IMULOR full descriptions are empty in all three locales; no replacement medical copy was invented.
- Composition is missing in all three locales for MAXLER VIT and IMULOR.
- Purchase URLs and instruction/certificate files are shown only when an administrator provides them.
- Article reviewer fields are currently empty. The UI and JSON-LD do not claim medical review without a real name and review date.
- Legal privacy-policy and terms pages are not present, so footer links were not fabricated.

## UI, accessibility and performance work

- Reusable, equal-height product and article cards with one clear link target.
- Product grid is 4 columns on desktop, 2 on tablet and 1 on mobile.
- Long names/descriptions are clamped; images reserve space to reduce CLS.
- Product/article images have responsive WebP variants, `srcset`, `sizes`, dimensions, lazy loading and async decoding below the fold.
- Product and rich article HTML passes through an allow-list sanitizer; raw Markdown markers and emoji headings are normalized.
- Catalog/news search controls and contact inputs use visible labels.
- Contact validation includes CSRF, server rules, honeypot, throttling, field associations, loading state and status/error semantics.
- Home is limited to 6 featured products and 3 articles; its duplicate form was replaced by a contacts CTA and its map link is real.
- Skip link, focus styling, active navigation, accessible mobile menu focus handling/Escape and reduced-motion rules are present.
- Full-screen loader was removed; Jivo is delayed; shared styles are cacheable assets.

## Automated verification

- `php -l`: application and translation PHP files pass.
- PHPUnit/Laravel Feature tests: **17 passed**.
- The scope test requests all **60 pages** and checks status 200, one H1, locale, canonical, hreflang, raw Markdown, dead hash links and sitemap coverage.
- Local server smoke checks passed for representative home, catalog, product, news, article, author, contacts and sitemap URLs.
- Sitemap contains exactly **60** `<url>` entries.

## Lighthouse and visual QA status

Lighthouse scores and screenshot-based viewport checks were not measured in this environment because the required browser control interface was unavailable. No score is claimed. Responsive CSS and server-rendered HTML were verified automatically, but final device/browser QA is still required at 360×800, 390×844, 768×1024, 1024×768 and 1440×900.

## Remaining business inputs / TODO

1. Qualified medical/regulatory approval for every flagged claim and the RU/EN disclaimer wording.
2. Approved full IMULOR copy and composition in UZ/RU/EN.
3. Approved MAXLER VIT composition in UZ/RU/EN.
4. Real purchase/partner URLs and instruction/certificate PDFs per product where applicable.
5. Confirmed reviewer names, credentials and review dates per article, if reviews actually occur.
6. Approved privacy policy and terms text/routes before adding footer links.
7. Department ownership for the two published phone numbers, if more specific labels than primary/additional contact are desired.
8. Production Lighthouse/Web Vitals measurement and real-device visual QA.
9. Upgrade planning for Laravel 8/PHP 8.5 compatibility; current vendor packages emit deprecation notices under PHP 8.5.
