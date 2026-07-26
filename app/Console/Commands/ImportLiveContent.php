<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ImportLiveContent extends Command
{
    protected $signature = 'content:import-live
        {--replace : Replace existing local products, articles and partners}
        {--no-translate : Keep English fields empty instead of translating them}';

    protected $description = 'Copy public website content from the production PostgreSQL database to local SQLite';

    private array $translationCache = [];

    private const PRODUCT_NAMES = [
        1 => 'Neo Magnesium B6',
        2 => 'Zinc +',
        3 => 'Neo Calcium D3',
        4 => 'FERFOLAS',
        5 => 'FERFOLAS',
        6 => 'MAXLER VIT',
        7 => 'IMULOR',
        8 => 'Magnesium Forte B6',
        9 => 'PIKAMIN',
    ];

    private const ARTICLE_TITLES = [
        1 => 'The Power of Prevention: Big Changes Through Simple Habits',
        2 => 'New Medical Advances in 2025',
        3 => "Women's Health: Frequently Asked Questions and Answers",
        6 => 'What Happens to the Body When Magnesium Is Deficient? Symptoms, Consequences, and Prevention',
    ];

    private const PARTNER_URLS = [
        1 => 'https://uzum.uz/uz/shop/life-prime',
        2 => null,
        3 => 'https://www.family-calm.uz/',
        4 => 'https://www.instagram.com/biofituz/',
    ];

    public function handle(): int
    {
        $localDatabase = database_path('database.sqlite');

        if (! is_file($localDatabase)) {
            $this->error("Local SQLite database does not exist: {$localDatabase}");

            return self::FAILURE;
        }

        Config::set('database.connections.neo_local', [
            'driver' => 'sqlite',
            'url' => null,
            'database' => $localDatabase,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('neo_local');

        $source = DB::connection('pgsql');
        $target = DB::connection('neo_local');

        $this->assertSourceTables($source);
        $this->assertTargetTables();

        $existing = [
            'products' => $target->table('products')->count(),
            'articles' => $target->table('articles')->count(),
            'partners' => $target->table('partners')->count(),
        ];

        if (array_sum($existing) > 0 && ! $this->option('replace')) {
            $this->error('Local content tables are not empty. Re-run with --replace to overwrite them.');

            return self::FAILURE;
        }

        $backupPath = $this->backupDatabase($localDatabase);
        $this->line("Backup created: {$backupPath}");

        $products = $source->table('products')->orderBy('id')->get();
        $articles = $source->table('articles')->orderBy('id')->get();
        $partners = $source->table('partners')->orderBy('id')->get();

        $translate = ! $this->option('no-translate');

        $target->transaction(function () use ($target, $products, $articles, $partners, $translate) {
            if ($this->option('replace')) {
                $target->table('products')->delete();
                $target->table('articles')->delete();
                $target->table('partners')->delete();
            }

            $productColumns = array_flip(Schema::connection('neo_local')->getColumnListing('products'));
            $articleColumns = array_flip(Schema::connection('neo_local')->getColumnListing('articles'));
            $partnerColumns = array_flip(Schema::connection('neo_local')->getColumnListing('partners'));

            $productBar = $this->output->createProgressBar($products->count());
            $productBar->start();

            foreach ($products as $product) {
                $row = array_intersect_key((array) $product, $productColumns);
                $nameEn = self::PRODUCT_NAMES[$product->id]
                    ?? ($translate ? $this->translate($product->name_ru ?: $product->name) : null);
                $typeEn = $translate ? $this->translate($product->type_ru ?: $product->type) : null;
                $compositionEn = $translate ? $this->translate($product->composition_ru ?: $product->composition) : null;
                $descriptionEn = $translate ? $this->translate($product->description_ru ?: $product->description) : null;

                $row = array_merge($row, [
                    'name_en' => $nameEn,
                    'type_en' => $typeEn,
                    'composition_en' => $compositionEn,
                    'description_en' => $descriptionEn,
                    'seo_title_uz' => $this->seoTitle($product->name_uz),
                    'seo_title_ru' => $this->seoTitle($product->name_ru),
                    'seo_title_en' => $this->seoTitle($nameEn),
                    'meta_description_uz' => $this->summary($product->description_uz ?: $product->type_uz),
                    'meta_description_ru' => $this->summary($product->description_ru ?: $product->type_ru),
                    'meta_description_en' => $this->summary($descriptionEn ?: $typeEn),
                    'schema_description_uz' => $this->summary($product->description_uz ?: $product->type_uz, 300),
                    'schema_description_ru' => $this->summary($product->description_ru ?: $product->type_ru, 300),
                    'schema_description_en' => $this->summary($descriptionEn ?: $typeEn, 300),
                    'og_image' => $product->image,
                    'robots' => 'index,follow',
                    'sku' => sprintf('NEO-%03d', $product->id),
                ]);

                $target->table('products')->insert(array_intersect_key($row, $productColumns));
                $productBar->advance();
            }

            $productBar->finish();
            $this->newLine();

            $articleBar = $this->output->createProgressBar($articles->count());
            $articleBar->start();

            foreach ($articles as $article) {
                $row = array_intersect_key((array) $article, $articleColumns);
                $titleEn = self::ARTICLE_TITLES[$article->id]
                    ?? ($translate ? $this->translate($article->title_ru) : null);
                $descriptionEn = $translate ? $this->translate($article->description_ru) : null;

                $row = array_merge($row, [
                    'title_en' => $titleEn,
                    'description_en' => $descriptionEn,
                    'seo_title_uz' => $this->seoTitle($article->title_uz),
                    'seo_title_ru' => $this->seoTitle($article->title_ru),
                    'seo_title_en' => $this->seoTitle($titleEn),
                    'meta_description_uz' => $this->summary($article->description_uz),
                    'meta_description_ru' => $this->summary($article->description_ru),
                    'meta_description_en' => $this->summary($descriptionEn),
                    'schema_description_uz' => $this->summary($article->description_uz, 300),
                    'schema_description_ru' => $this->summary($article->description_ru, 300),
                    'schema_description_en' => $this->summary($descriptionEn, 300),
                    'og_image' => $article->photo,
                    'robots' => 'index,follow',
                    'author_name' => 'NEO-LABS Editorial Team',
                    'author_role_uz' => 'Tibbiy kontent tahririyati',
                    'author_role_ru' => 'Редакция медицинского контента',
                    'author_role_en' => 'Medical Content Editorial Team',
                    'author_slug' => 'neo-labs-editorial-team',
                    'schema_type' => 'BlogPosting',
                ]);

                $target->table('articles')->insert(array_intersect_key($row, $articleColumns));
                $articleBar->advance();
            }

            $articleBar->finish();
            $this->newLine();

            foreach ($partners as $partner) {
                $row = array_intersect_key((array) $partner, $partnerColumns);
                $row['url'] = self::PARTNER_URLS[$partner->id] ?? $partner->url;
                $target->table('partners')->insert(array_intersect_key($row, $partnerColumns));
            }
        });

        $this->newLine();
        $this->info(sprintf(
            'Imported %d products, %d articles and %d partners into local SQLite.',
            $products->count(),
            $articles->count(),
            $partners->count()
        ));

        return self::SUCCESS;
    }

    private function assertSourceTables(ConnectionInterface $source): void
    {
        foreach (['products', 'articles', 'partners'] as $table) {
            if (! $source->getSchemaBuilder()->hasTable($table)) {
                throw new RuntimeException("Production table is missing: {$table}");
            }
        }
    }

    private function assertTargetTables(): void
    {
        foreach (['products', 'articles', 'partners'] as $table) {
            if (! Schema::connection('neo_local')->hasTable($table)) {
                throw new RuntimeException("Local table is missing: {$table}. Run local migrations first.");
            }
        }
    }

    private function backupDatabase(string $database): string
    {
        $backupDirectory = database_path('backups');

        if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0755, true) && ! is_dir($backupDirectory)) {
            throw new RuntimeException("Unable to create backup directory: {$backupDirectory}");
        }

        $backup = $backupDirectory.'/database-before-live-import-'.date('Ymd-His').'.sqlite';

        if (! copy($database, $backup)) {
            throw new RuntimeException("Unable to create database backup: {$backup}");
        }

        return $backup;
    }

    private function translate(?string $source): ?string
    {
        if ($source === null || trim($source) === '') {
            return null;
        }

        $key = hash('sha256', $source);

        if (array_key_exists($key, $this->translationCache)) {
            return $this->translationCache[$key];
        }

        $response = Http::asForm()
            ->timeout(45)
            ->retry(3, 750)
            ->post('https://translate.googleapis.com/translate_a/single', [
                'client' => 'gtx',
                'sl' => 'ru',
                'tl' => 'en',
                'dt' => 't',
                'q' => $source,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('English translation failed with HTTP '.$response->status().'.');
        }

        $segments = $response->json()[0] ?? null;

        if (! is_array($segments)) {
            throw new RuntimeException('English translation returned an unexpected response.');
        }

        $translation = '';

        foreach ($segments as $segment) {
            if (is_array($segment) && isset($segment[0])) {
                $translation .= $segment[0];
            }
        }

        if (trim($translation) === '') {
            throw new RuntimeException('English translation returned an empty result.');
        }

        return $this->translationCache[$key] = $translation;
    }

    private function seoTitle(?string $title): ?string
    {
        return $title ? $title.' | NEO-LABS' : null;
    }

    private function summary(?string $html, int $limit = 155): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $html = (string) preg_replace(
            '/<\/?(?:address|article|aside|blockquote|br|div|figcaption|figure|footer|h[1-6]|header|li|main|nav|ol|p|section|table|td|th|tr|ul)\b[^>]*>/iu',
            ' ',
            $html
        );
        $plain = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = trim((string) preg_replace('/\s+/u', ' ', $plain));

        if (mb_strlen($plain) <= $limit) {
            return $plain;
        }

        $short = mb_substr($plain, 0, $limit - 1);
        $space = mb_strrpos($short, ' ');

        if ($space !== false && $space > (int) ($limit * 0.65)) {
            $short = mb_substr($short, 0, $space);
        }

        return rtrim($short, " \t\n\r\0\x0B,.;:-").'…';
    }
}
