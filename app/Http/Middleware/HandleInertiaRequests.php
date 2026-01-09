<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        // Load settings once
        $settings = Setting::first();

        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'locale' => app()->getLocale(),
            'locales' => ['pl', 'en'],
            'translations' => array_merge(
                __('ui'),
                [
                    'landing' => __('landing'),
                    'nav' => __('nav'),
                    'settings' => __('settings'),
                    'common' => __('common'),
                ]
            ),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'pendingApprovalsCount' => \App\Models\Appointment::where('status', \App\Models\Appointment::STATUS_PENDING_APPROVAL)
                ->whereNotNull('requested_starts_at')
                ->count(),
            'settings' => [
                'app_name' => $settings?->app_name ?? config('app.name'),
                'app_logo' => $settings?->app_logo ? Storage::url($settings->app_logo) : null,
            ],
        ];
    }
}
