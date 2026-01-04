<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SiteVisit;
use App\Models\Client;

class StatisticsController extends Controller
{
    public function index()
    {
        $totalVisits = SiteVisit::count();
        $uniqueVisitors = SiteVisit::distinct('ip_address')->count('ip_address');

        // Clients with at least one visit (tracked via client_id)
        $activeClients = Client::whereHas('siteVisits')
            ->withCount('siteVisits')
            ->orderByDesc('site_visits_count')
            ->with(['siteVisits' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->full_name,
                    'phone' => $client->phone_e164,
                    'visit_count' => $client->site_visits_count,
                    'last_visit' => $client->siteVisits->first()?->created_at->diffForHumans(),
                ];
            });

        // Clients with 0 visits
        $inactiveClients = Client::doesntHave('siteVisits')
            ->select(['id', 'full_name', 'phone_e164'])
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->full_name,
                    'phone' => $client->phone_e164,
                ];
            });

        return Inertia::render('Statistics/Index', [
            'totalVisits' => $totalVisits,
            'uniqueVisitors' => $uniqueVisitors,
            'activeClients' => $activeClients,
            'inactiveClients' => $inactiveClients,
        ]);
    }
}
