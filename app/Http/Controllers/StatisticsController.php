<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SiteVisit;
use App\Models\User;

class StatisticsController extends Controller
{
    public function index()
    {
        $totalVisits = SiteVisit::count();
        $uniqueVisitors = SiteVisit::distinct('ip_address')->count('ip_address');

        // Users with at least one visit
        $activeClients = User::whereHas('siteVisits')
            ->withCount('siteVisits')
            ->orderByDesc('site_visits_count')
            ->with(['siteVisits' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'visit_count' => $user->site_visits_count,
                    'last_visit' => $user->siteVisits->first()?->created_at->diffForHumans(),
                ];
            });

        // Users with 0 visits
        $inactiveClients = User::doesntHave('siteVisits')
            ->select(['id', 'name', 'email'])
            ->get();

        return Inertia::render('Statistics/Index', [
            'totalVisits' => $totalVisits,
            'uniqueVisitors' => $uniqueVisitors,
            'activeClients' => $activeClients,
            'inactiveClients' => $inactiveClients,
        ]);
    }
}
