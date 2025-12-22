<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\SmsMessage;
use App\Models\Setting;
use App\Services\DashboardCache;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->input('range', '30d');
        $from = $request->input('from');
        $to = $request->input('to');
        
        $timezone = Setting::get('timezone', config('app.timezone', 'UTC'));
        
        [$startDate, $endDate] = $this->resolveRange($range, $from, $to, $timezone);

        $version = DashboardCache::getVersion();
        $cacheKey = "dashboard_metrics_v{$version}_{$range}_{$from}_{$to}_{$timezone}";
        
        $data = Cache::remember($cacheKey, now()->addHours(1), function () use ($startDate, $endDate, $timezone) {
            return [
                'totals' => [
                    'clients' => Client::count(),
                    'appointments' => Appointment::count(),
                ],
                'period' => [
                    'new_clients' => Client::whereBetween('created_at', [$startDate, $endDate])->count(),
                    'appointments' => Appointment::whereBetween('starts_at', [$startDate, $endDate])->count(),
                    'sms_sent' => SmsMessage::where('status', 'success')
                        ->whereBetween('sent_at', [$startDate, $endDate])->count(),
                    'rescheduled_appointments' => Appointment::whereBetween('last_rescheduled_at', [$startDate, $endDate])
                        ->count(),
                ],
                'timeseries' => [
                    'clients' => $this->getClientsOverTime($startDate, $endDate, $timezone),
                    'appointments' => $this->getAppointmentsOverTime($startDate, $endDate, $timezone),
                    'sms' => $this->getSmsOverTime($startDate, $endDate, $timezone),
                    'reschedules' => $this->getReschedulesOverTime($startDate, $endDate, $timezone),
                ],
            ];
        });

        return Inertia::render('Dashboard', array_merge($data, [
            'filters' => [
                'range' => $range,
                'from' => $from,
                'to' => $to,
            ],
        ]));
    }

    private function resolveRange($range, $from, $to, $timezone)
    {
        $now = Carbon::now($timezone);
        $endDate = $now->copy()->endOfDay()->utc();

        switch ($range) {
            case '7d':
                $startDate = $now->copy()->subDays(6)->startOfDay()->utc();
                break;
            case '90d':
                $startDate = $now->copy()->subDays(89)->startOfDay()->utc();
                break;
            case 'mtd':
                $startDate = $now->copy()->startOfMonth()->utc();
                break;
            case 'custom':
                if ($from && $to) {
                    $startDate = Carbon::parse($from, $timezone)->startOfDay()->utc();
                    $endDate = Carbon::parse($to, $timezone)->endOfDay()->utc();
                    break;
                }
                // fallthrough to default if missing dates
            case '30d':
            default:
                $startDate = $now->copy()->subDays(29)->startOfDay()->utc();
                break;
        }

        return [$startDate, $endDate];
    }

    private function getClientsOverTime($start, $end, $timezone)
    {
        $data = Client::whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy(fn($item) => $item->created_at->timezone($timezone)->format('Y-m-d'));

        return $this->formatTimeSeries($data, $start, $end, $timezone);
    }

    private function getAppointmentsOverTime($start, $end, $timezone)
    {
        $data = Appointment::whereBetween('starts_at', [$start, $end])
            ->get()
            ->groupBy(fn($item) => $item->starts_at->timezone($timezone)->format('Y-m-d'));

        return $this->formatTimeSeries($data, $start, $end, $timezone);
    }

    private function getSmsOverTime($start, $end, $timezone)
    {
        $data = SmsMessage::where('status', 'success')
            ->whereBetween('sent_at', [$start, $end])
            ->get()
            ->groupBy(fn($item) => $item->sent_at->timezone($timezone)->format('Y-m-d'));

        return $this->formatTimeSeries($data, $start, $end, $timezone);
    }

    private function getReschedulesOverTime($start, $end, $timezone)
    {
        $data = Appointment::whereBetween('last_rescheduled_at', [$start, $end])
            ->get()
            ->groupBy(fn($item) => $item->last_rescheduled_at->timezone($timezone)->format('Y-m-d'));

        return $this->formatTimeSeries($data, $start, $end, $timezone);
    }

    private function formatTimeSeries($groupedData, $start, $end, $timezone)
    {
        $results = [];
        $current = Carbon::parse($start)->timezone($timezone);
        $stop = Carbon::parse($end)->timezone($timezone);

        while ($current <= $stop) {
            $dateStr = $current->format('Y-m-d');
            $results[] = [
                'date' => $dateStr,
                'count' => isset($groupedData[$dateStr]) ? $groupedData[$dateStr]->count() : 0,
            ];
            $current->addDay();
        }

        return $results;
    }
}
