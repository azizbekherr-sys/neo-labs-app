<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    public function index(Request $request, AnalyticsReport $analytics)
    {
        $days = $analytics->period((int) $request->input('days', 30));
        return view('admin.analytics.index', [
            'report' => $analytics->get($days),
            'periods' => AnalyticsReport::PERIODS,
        ]);
    }

    public function export(Request $request, AnalyticsReport $analytics)
    {
        $days = $analytics->period((int) $request->input('days', 30));
        $from = now()->subDays($days - 1)->startOfDay();
        $filename = 'neo-labs-analytics-' . now()->format('Y-m-d') . '-' . $days . 'days.csv';

        return response()->streamDownload(function () use ($from) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Vaqt', 'Hodisa', 'Sahifa', 'Sarlavha', 'Sahifa turi', 'Kanal', 'Manba',
                'Referrer', 'Qurilma', 'Brauzer', 'Operatsion tizim', 'Mamlakat', 'Shahar',
                'Vaqt zonasi', 'Til', 'Maqsad URL',
            ], ';');

            foreach (DB::table('page_views')->where('occurred_at', '>=', $from)->orderBy('occurred_at')->cursor() as $row) {
                fputcsv($stream, array_map([$this, 'csvValue'], [
                    $row->occurred_at, $row->event_type, $row->path, $row->title, $row->page_type,
                    $row->channel, $row->source, $row->referrer_host, $row->device_type, $row->browser,
                    $row->operating_system, $row->country_code, $row->city, $row->timezone, $row->locale, $row->target_url,
                ]), ';');
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvValue($value): string
    {
        $value = (string) ($value ?? '');
        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }
}
