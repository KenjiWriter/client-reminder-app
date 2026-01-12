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
                    'canceled' => Appointment::where('status', 'canceled')->count(),
                ],
                'period' => [
                    'new_clients' => Client::whereBetween('created_at', [$startDate, $endDate])->count(),
                    'appointments' => Appointment::whereBetween('starts_at', [$startDate, $endDate])->count(),
                    'sms_sent' => SmsMessage::where('status', 'success')
                        ->whereBetween('sent_at', [$startDate, $endDate])->count(),
                    'rescheduled_appointments' => Appointment::whereBetween('last_rescheduled_at', [$startDate, $endDate])
                        ->count(),
                    'canceled' => Appointment::where('status', 'canceled')
                        ->whereBetween('starts_at', [$startDate, $endDate])->count(),
                ],
                'timeseries' => [
                    'clients' => $this->getClientsOverTime($startDate, $endDate, $timezone),
                    'appointments' => $this->getAppointmentsOverTime($startDate, $endDate, $timezone),
                    'sms' => $this->getSmsOverTime($startDate, $endDate, $timezone),
                    'reschedules' => $this->getReschedulesOverTime($startDate, $endDate, $timezone),
                    'canceled' => $this->getCanceledOverTime($startDate, $endDate, $timezone),
                    'unpaid' => $this->getUnpaidOverTime($startDate, $endDate, $timezone),
                ],
                'analytics' => $this->getAnalytics($startDate, $endDate),
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
                $endDate = $now->copy()->endOfMonth()->utc();
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

    private function getCanceledOverTime($start, $end, $timezone)
    {
        $data = Appointment::where('status', 'canceled')
            ->whereBetween('starts_at', [$start, $end])
            ->get()
            ->groupBy(fn($item) => $item->starts_at->timezone($timezone)->format('Y-m-d'));

        return $this->formatTimeSeries($data, $start, $end, $timezone);
    }

    private function getUnpaidOverTime($start, $end, $timezone)
    {
        $data = Appointment::where('is_paid', false)
            ->where('status', '!=', 'canceled')
            ->whereBetween('starts_at', [$start, $end])
            ->get()
            ->groupBy(fn($item) => $item->starts_at->timezone($timezone)->format('Y-m-d'));

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

    private function getAnalytics($start, $end)
    {
        // Fetch appointments with their related service
        $appointments = Appointment::with('service')
            ->whereBetween('starts_at', [$start, $end])
            ->get();

        // 1. Service Breakdown
        // Filter out appointments without a service just in case
        $serviceBreakdown = $appointments->whereNotNull('service')
            ->groupBy(fn($appointment) => $appointment->service->name)
            ->map(function ($group, $serviceName) {
                return [
                    'name' => $serviceName,
                    'count' => $group->count(),
                ];
            })
            ->values()
            ->sortByDesc('count')
            ->values();

        // 2. Projected Revenue
        $projectedRevenue = $appointments->sum(function ($appointment) {
            return $appointment->service ? $appointment->service->price : 0;
        });

        // 3. Top Performing Service
        $topService = $serviceBreakdown->first();

        // 4. Total Visits
        $totalVisits = $appointments->count();

        return [
            'projected_revenue' => $projectedRevenue,
            'service_breakdown' => $serviceBreakdown,
            'top_service' => $topService ? $topService['name'] : 'N/A',
            'total_visits' => $totalVisits,
        ];
    }
}
