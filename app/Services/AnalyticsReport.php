<?php

namespace App\Services;

use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsReport
{
    public const PERIODS = [7, 30, 90, 365];

    public function period(int $days): int
    {
        return in_array($days, self::PERIODS, true) ? $days : 30;
    }

    public function get(int $days): array
    {
        $days = $this->period($days);
        if (!Schema::hasTable('page_views')) return $this->emptyReport($days);

        return Cache::remember('analytics.report.v2.' . $days, 60, fn () => $this->build($days));
    }

    public function summary(int $days = 30): array
    {
        $days = $this->period($days);
        if (!Schema::hasTable('page_views')) return $this->emptySummary();

        return Cache::remember('analytics.summary.v2.' . $days, 60, function () use ($days) {
            $from = now()->subDays($days - 1)->startOfDay();
            return $this->summaryQuery($from);
        });
    }

    private function build(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $pageViews = $this->pageViews($from);
        $summary = $this->summaryQuery($from);

        $sessionTotals = (clone $pageViews)
            ->select('session_id')
            ->selectRaw('COUNT(*) as views')
            ->groupBy('session_id');
        $bounces = DB::query()->fromSub($sessionTotals, 'session_totals')->where('views', 1)->count();
        $summary['bounce_rate'] = $summary['sessions'] > 0
            ? round(($bounces / $summary['sessions']) * 100, 1)
            : 0.0;
        $summary['pages_per_session'] = $summary['sessions'] > 0
            ? round($summary['page_views'] / $summary['sessions'], 2)
            : 0.0;

        $rawTrend = (clone $pageViews)
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as views, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');
        $trend = [];
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), now()->startOfDay()) as $date) {
            $key = $date->format('Y-m-d');
            $row = $rawTrend->get($key);
            $trend[] = ['day' => $key, 'views' => (int) ($row->views ?? 0), 'visitors' => (int) ($row->visitors ?? 0)];
        }

        $topPages = (clone $pageViews)
            ->select('path', 'page_type')
            ->selectRaw('MAX(title) as title, COUNT(*) as views, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('path', 'page_type')->orderByDesc('views')->limit(15)->get()->map(fn ($row) => (array) $row)->all();

        $topContent = (clone $pageViews)
            ->whereIn('page_type', ['product', 'article'])
            ->whereNotNull('content_id')
            ->select('page_type', 'content_id', 'path')
            ->selectRaw('MAX(title) as title, COUNT(*) as views, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('page_type', 'content_id', 'path')->orderByDesc('views')->limit(16)->get()->map(fn ($row) => (array) $row)->all();

        $events = DB::table('page_views')->where('occurred_at', '>=', $from)
            ->where('event_type', '!=', 'page_view')
            ->select('event_type')->selectRaw('COUNT(*) as total')
            ->groupBy('event_type')->orderByDesc('total')->get()->map(fn ($row) => (array) $row)->all();

        $recent = (clone $pageViews)->select([
            'occurred_at', 'title', 'path', 'page_type', 'channel', 'source', 'device_type',
            'browser', 'operating_system', 'country_code', 'city', 'timezone', 'locale', 'referrer_host',
        ])->latest('occurred_at')->limit(30)->get()->map(fn ($row) => (array) $row)->all();

        return [
            'days' => $days,
            'from' => $from,
            'summary' => $summary,
            'trend' => $trend,
            'top_pages' => $topPages,
            'top_content' => $topContent,
            'channels' => $this->distribution($pageViews, 'channel', 10),
            'devices' => $this->distribution($pageViews, 'device_type', 8),
            'browsers' => $this->distribution($pageViews, 'browser', 8),
            'systems' => $this->distribution($pageViews, 'operating_system', 8),
            'countries' => $this->distribution($pageViews, 'country_code', 12, true),
            'timezones' => $this->distribution($pageViews, 'timezone', 10, true),
            'locales' => $this->distribution($pageViews, 'locale', 6, true),
            'referrers' => $this->distribution($pageViews, 'referrer_host', 12, true),
            'events' => $events,
            'recent' => $recent,
        ];
    }

    private function summaryQuery($from): array
    {
        $row = $this->pageViews($from)->selectRaw(
            'COUNT(*) as page_views, COUNT(DISTINCT visitor_id) as visitors, COUNT(DISTINCT session_id) as sessions'
        )->first();

        return [
            'page_views' => (int) ($row->page_views ?? 0),
            'visitors' => (int) ($row->visitors ?? 0),
            'sessions' => (int) ($row->sessions ?? 0),
            'pages_per_session' => 0.0,
            'bounce_rate' => 0.0,
        ];
    }

    private function pageViews($from)
    {
        return DB::table('page_views')->where('event_type', 'page_view')->where('occurred_at', '>=', $from);
    }

    private function distribution($query, string $column, int $limit, bool $excludeEmpty = false): array
    {
        $builder = clone $query;
        if ($excludeEmpty) $builder->whereNotNull($column)->where($column, '!=', '');
        return $builder->selectRaw($column . ' as label, COUNT(*) as total, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy($column)->orderByDesc('total')->limit($limit)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function emptySummary(): array
    {
        return ['page_views' => 0, 'visitors' => 0, 'sessions' => 0, 'pages_per_session' => 0.0, 'bounce_rate' => 0.0];
    }

    private function emptyReport(int $days): array
    {
        return [
            'days' => $days, 'from' => now()->subDays($days - 1)->startOfDay(), 'summary' => $this->emptySummary(),
            'trend' => [], 'top_pages' => [], 'top_content' => [], 'channels' => [], 'devices' => [],
            'browsers' => [], 'systems' => [], 'countries' => [], 'timezones' => [], 'locales' => [],
            'referrers' => [], 'events' => [], 'recent' => [],
        ];
    }
}
