<?php
/**
 * Scrape ALL content from the live site https://neo-labs.uz and import it
 * into the configured (Supabase) database. Products (ru+uz), articles (ru+uz)
 * and partners are extracted from the rendered HTML; referenced images are
 * downloaded into public/ so they render locally. Live IDs are preserved.
 */

$PROJECT = '/Users/macbookair/Documents/neo-labs.uz';
require $PROJECT.'/vendor/autoload.php';
$app = require_once $PROJECT.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Partner;
use App\Models\Article;
use Illuminate\Support\Facades\DB;

const BASE = 'https://neo-labs.uz';
$PUBLIC = $PROJECT.'/public';

function fetch(string $url): ?string {
    // Retry with backoff; use a real browser UA so the live site does not throttle us.
    for ($attempt = 1; $attempt <= 4; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body !== false && $code >= 200 && $code < 400) return $body;
        usleep(700000 * $attempt); // 0.7s, 1.4s, 2.1s backoff
    }
    return null;
}

function dom(string $html): DOMXPath {
    libxml_use_internal_errors(true);
    $d = new DOMDocument();
    // The page carries <meta charset="UTF-8">, so libxml decodes UTF-8 correctly.
    $d->loadHTML($html);
    libxml_clear_errors();
    return new DOMXPath($d);
}

function xText(DOMXPath $xp, string $q): ?string {
    $n = $xp->query($q)->item(0);
    return $n ? trim(preg_replace('/\s+/', ' ', $n->textContent)) : null;
}

function xInner(DOMXPath $xp, string $q): ?string {
    $n = $xp->query($q)->item(0);
    if (!$n) return null;
    $html = '';
    foreach ($n->childNodes as $c) $html .= $n->ownerDocument->saveHTML($c);
    $html = trim($html);
    // strip the read-more fade artifact if present
    $html = preg_replace('#<div[^>]*class="[^"]*p-fade[^"]*"[^>]*>.*?</div>#is', '', $html);
    return trim($html) ?: null;
}

/** Collect product image srcs (stage + thumbs) as relative products/ paths */
function productImages(DOMXPath $xp): array {
    $out = [];
    foreach (['//div[contains(@class,"p-stage")]//img/@src', '//div[contains(@class,"p-thumb")]//img/@src'] as $q) {
        foreach ($xp->query($q) as $a) {
            $src = $a->nodeValue;
            if (!$src) continue;
            $path = parse_url($src, PHP_URL_PATH) ?: $src;
            $path = ltrim(urldecode($path), '/');           // e.g. products/p_xxx.png
            if (stripos($path, 'placeholder') !== false) continue;
            if ($path !== '' && !in_array($path, $out, true)) $out[] = $path;
        }
    }
    return $out;
}

function downloadImage(string $relPath): void {
    global $PUBLIC;
    $dest = $PUBLIC.'/'.$relPath;
    if (is_file($dest) && filesize($dest) > 0) return;         // already have it
    @mkdir(dirname($dest), 0775, true);
    $bin = fetch(BASE.'/'.$relPath);
    if ($bin !== null && strlen($bin) > 0) {
        file_put_contents($dest, $bin);
        echo "    downloaded $relPath (".strlen($bin)." bytes)\n";
    } else {
        echo "    !! could not download $relPath\n";
    }
}

// ---------------------------------------------------------------------------
// 1) PRODUCTS
// ---------------------------------------------------------------------------
$productIds = [1,2,3,4,5,6,7,8,9];
$products = [];
foreach ($productIds as $id) {
    $ruHtml = fetch(BASE."/ru/product/$id/x");
    $uzHtml = fetch(BASE."/uz/product/$id/x");
    if (!$ruHtml) { echo "product $id: RU fetch failed, skipping\n"; continue; }
    $ru = dom($ruHtml);
    $uz = $uzHtml ? dom($uzHtml) : $ru;

    $imgs = productImages($ru);
    $row = [
        'id'              => $id,
        'name_ru'         => xText($ru, '//h1[contains(@class,"p-title")]'),
        'name_uz'         => xText($uz, '//h1[contains(@class,"p-title")]'),
        'type_ru'         => xText($ru, '//div[contains(@class,"p-sub")]'),
        'type_uz'         => xText($uz, '//div[contains(@class,"p-sub")]'),
        'composition_ru'  => xInner($ru, '//*[@id="p-comp-sec"]//div[contains(@class,"p-desc")]'),
        'composition_uz'  => xInner($uz, '//*[@id="p-comp-sec"]//div[contains(@class,"p-desc")]'),
        'description_ru'  => xInner($ru, '//div[contains(@class,"p-read-content")]'),
        'description_uz'  => xInner($uz, '//div[contains(@class,"p-read-content")]'),
        'images'          => $imgs,
    ];
    // normalize '—' placeholders to null
    foreach (['type_ru','type_uz','composition_ru','composition_uz','description_ru','description_uz'] as $k) {
        if ($row[$k] !== null && trim(strip_tags($row[$k])) === '—') $row[$k] = null;
    }
    $products[] = $row;
    foreach ($imgs as $rel) downloadImage($rel);
    echo "product $id: ".($row['name_ru'] ?? '?')." / ".($row['name_uz'] ?? '?')." | imgs=".count($imgs)."\n";
}

// ---------------------------------------------------------------------------
// 2) ARTICLES
// ---------------------------------------------------------------------------
$articleIds = [1,2,3,6];
$articles = [];
foreach ($articleIds as $id) {
    $ruHtml = fetch(BASE."/ru/news/$id/x");
    $uzHtml = fetch(BASE."/uz/news/$id/x");
    if (!$ruHtml) { echo "article $id: RU fetch failed, skipping\n"; continue; }
    $ru = dom($ruHtml);
    $uz = $uzHtml ? dom($uzHtml) : $ru;

    // photo from JSON-LD schema image, fallback to article header img
    $photo = null;
    if (preg_match('#"image":\s*\[\s*"([^"]+articles/[^"]+)"#', $ruHtml, $m)) {
        $photo = ltrim(parse_url(html_entity_decode($m[1]), PHP_URL_PATH), '/');
    }
    if (!$photo) {
        $src = $ru->query('//article//img/@src')->item(0);
        if ($src) $photo = ltrim(parse_url($src->nodeValue, PHP_URL_PATH) ?: '', '/');
    }

    // views + published date
    $views = 0;
    if (preg_match('#(?:Просмотров|Ko.rishlar)[^0-9]*([0-9][0-9 ]*)#u', $ruHtml, $m)) {
        $views = (int) str_replace(' ', '', $m[1]);
    }
    $created = null;
    if (preg_match('#(\d{2})\.(\d{2})\.(\d{4})#', strip_tags($ruHtml), $m)) {
        $created = "{$m[3]}-{$m[2]}-{$m[1]} 00:00:00";
    }

    $row = [
        'id'             => $id,
        'title_ru'       => xText($ru, '//article//h1'),
        'title_uz'       => xText($uz, '//article//h1'),
        'description_ru' => xInner($ru, '//article//div[contains(@class,"content")]'),
        'description_uz' => xInner($uz, '//article//div[contains(@class,"content")]'),
        'photo'          => $photo,
        'views'          => $views,
        'created_at'     => $created,
    ];
    $articles[] = $row;
    if ($photo) downloadImage($photo);
    echo "article $id: ".($row['title_ru'] ?? '?')." | views=$views photo=".($photo ?: '-')."\n";
}

// ---------------------------------------------------------------------------
// 3) PARTNERS  (manufacturing page)
// ---------------------------------------------------------------------------
$partners = [];
$manuHtml = fetch(BASE.'/ru/manufacturing');
if ($manuHtml) {
    $xp = dom($manuHtml);
    foreach ($xp->query('//img/@src') as $a) {
        $src = $a->nodeValue;
        $path = ltrim(parse_url($src, PHP_URL_PATH) ?: '', '/');
        if (stripos($path, 'partners/') === 0 && !in_array($path, $partners, true)) {
            $partners[] = $path;
        }
    }
}
foreach ($partners as $rel) downloadImage($rel);
echo "partners: ".count($partners)."\n";

// ---------------------------------------------------------------------------
// 4) WRITE TO DATABASE (replace sample data)
// ---------------------------------------------------------------------------
echo "\n== writing to database ==\n";
DB::transaction(function () use ($products, $articles, $partners) {
    Product::truncate();
    Article::truncate();
    Partner::truncate();

    foreach ($products as $r) {
        $p = new Product();
        $p->id = $r['id'];
        $p->forceFill([
            'name'           => $r['name_ru'] ?? $r['name_uz'],
            'name_ru'        => $r['name_ru'],
            'name_uz'        => $r['name_uz'],
            'type'           => $r['type_ru'] ?? $r['type_uz'],
            'type_ru'        => $r['type_ru'],
            'type_uz'        => $r['type_uz'],
            'composition'    => $r['composition_ru'] ?? $r['composition_uz'],
            'composition_ru' => $r['composition_ru'],
            'composition_uz' => $r['composition_uz'],
            'description'    => $r['description_ru'] ?? $r['description_uz'],
            'description_ru' => $r['description_ru'],
            'description_uz' => $r['description_uz'],
            'status'         => 'active',
            'stock'          => 0,
            'image'          => $r['images'][0] ?? null,
            'images'         => $r['images'],
        ]);
        $p->save();
    }

    foreach ($articles as $r) {
        $a = new Article();
        $a->id = $r['id'];
        $a->forceFill([
            'title_ru'       => $r['title_ru'],
            'title_uz'       => $r['title_uz'],
            'description_ru' => $r['description_ru'],
            'description_uz' => $r['description_uz'],
            'photo'          => $r['photo'],
            'views'          => $r['views'],
        ]);
        if (!empty($r['created_at'])) { $a->created_at = $r['created_at']; }
        $a->save();
    }

    foreach ($partners as $path) {
        $pt = new Partner();
        $pt->forceFill(['path' => $path, 'url' => null])->save();
    }

    // reset Postgres sequences so future inserts don't collide with preserved IDs
    if (DB::connection()->getDriverName() === 'pgsql') {
        foreach (['products','articles','partners'] as $t) {
            DB::statement("SELECT setval(pg_get_serial_sequence('$t','id'), COALESCE((SELECT MAX(id) FROM $t), 1))");
        }
    }
});

echo "DONE: products=".Product::count()." articles=".Article::count()." partners=".Partner::count()."\n";
